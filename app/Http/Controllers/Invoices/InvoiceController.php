<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lead;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Invoice::where('workspace_id', $workspace->id)
            ->with(['lead:id,first_name,last_name,email,company_name', 'company:id,name', 'deal:id,title']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('lead', fn($l) => $l->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        $summary = [
            'total'       => Invoice::where('workspace_id', $workspace->id)->count(),
            'paid_count'  => Invoice::where('workspace_id', $workspace->id)->where('status', 'paid')->count(),
            'total_billed'=> (float) Invoice::where('workspace_id', $workspace->id)->where('status', '!=', 'cancelled')->sum('total'),
            'total_paid'  => (float) Invoice::where('workspace_id', $workspace->id)->sum('amount_paid'),
        ];

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'summary'  => $summary,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $nextNumber = 'INV-' . strtoupper(Str::random(6));

        return Inertia::render('Invoices/Create', [
            'nextNumber' => $nextNumber,
            'products'   => Product::where('workspace_id', $workspace->id)->where('is_active', true)->select('id', 'name', 'price', 'type')->get(),
            'leads'      => Lead::where('workspace_id', $workspace->id)->select('id', 'first_name', 'last_name', 'email', 'company_name')->limit(100)->get(),
            'companies'  => Company::where('workspace_id', $workspace->id)->select('id', 'name')->limit(100)->get(),
            'deals'      => Deal::where('workspace_id', $workspace->id)->select('id', 'title', 'value')->limit(100)->get(),
            'preselected' => [
                'lead_id'    => $request->input('lead_id'),
                'deal_id'    => $request->input('deal_id'),
                'company_id' => $request->input('company_id'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $validated = $request->validate([
            'invoice_number'  => 'required|string|max:50',
            'lead_id'         => 'nullable|exists:leads,id',
            'company_id'      => 'nullable|exists:companies,id',
            'deal_id'         => 'nullable|exists:deals,id',
            'status'          => 'required|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_paid'     => 'nullable|numeric|min:0',
            'issue_date'      => 'nullable|date',
            'due_date'        => 'nullable|date',
            'terms'           => 'nullable|string',
            'notes'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.item_name'   => 'required|string|max:255',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }

        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $total = max(0, $subtotal + $taxAmount - $discount);
        $paid = (float) ($validated['amount_paid'] ?? 0);

        $status = $validated['status'];
        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        }

        $invoice = Invoice::create([
            'workspace_id'    => $workspace->id,
            'invoice_number'  => $validated['invoice_number'],
            'lead_id'         => $validated['lead_id'],
            'company_id'      => $validated['company_id'],
            'deal_id'         => $validated['deal_id'],
            'user_id'         => $request->user()->id,
            'status'          => $status,
            'subtotal'        => $subtotal,
            'tax_rate'        => $taxRate,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discount,
            'total'           => $total,
            'amount_paid'     => $paid,
            'currency'        => 'USD',
            'issue_date'      => $validated['issue_date'] ?: now()->toDateString(),
            'due_date'        => $validated['due_date'] ?: now()->addDays(14)->toDateString(),
            'terms'           => $validated['terms'],
            'notes'           => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'product_id'  => $item['product_id'] ?? null,
                'item_name'   => $item['item_name'],
                'description' => $item['description'] ?? null,
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total'       => ($item['quantity'] * $item['unit_price']),
            ]);
        }

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($invoice->workspace_id !== $workspace->id, 403);

        $invoice->load([
            'items.product',
            'lead:id,first_name,last_name,email,phone,company_name',
            'company:id,name,domain,website,phone,address,city,country',
            'deal:id,title,value',
            'user:id,name,email',
            'workspace:id,name'
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($invoice->workspace_id !== $workspace->id, 403);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'notes'          => 'nullable|string',
        ]);

        $newPaid = (float)$invoice->amount_paid + (float)$request->amount;
        $status = ($newPaid >= (float)$invoice->total) ? 'paid' : 'partially_paid';

        $invoice->update([
            'amount_paid'    => $newPaid,
            'status'         => $status,
            'paid_at'        => ($status === 'paid') ? now() : $invoice->paid_at,
            'payment_method' => $request->payment_method ?: 'Bank Transfer',
        ]);

        return back()->with('success', 'Payment of $' . number_format($request->amount, 2) . ' recorded successfully.');
    }
}
