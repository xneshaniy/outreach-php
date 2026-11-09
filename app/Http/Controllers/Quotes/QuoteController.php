<?php

namespace App\Http\Controllers\Quotes;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteItem;
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

class QuoteController extends Controller
{
    protected function getWorkspace(Request $request): \App\Models\Workspace
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;
        if (!$workspace && $user) {
            $workspace = $user->workspaces()->first()
                ?? \App\Models\Workspace::where('is_active', true)->orderBy('name')->first()
                ?? \App\Models\Workspace::first();
            if ($workspace) {
                $user->current_workspace_id = $workspace->id;
                $user->saveQuietly();
            }
        }

        if (!$workspace) {
            $workspace = \App\Models\Workspace::create([
                'name' => 'Default Workspace',
                'slug' => 'default-' . Str::random(6),
                'is_active' => true,
            ]);
            if ($user) {
                $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
                $user->setCurrentWorkspace($workspace);
            }
        }

        return $workspace;
    }

    protected function ensureTables(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('quotes')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('QuoteController ensureTables migration error: ' . $e->getMessage());
            }
        }
    }

    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $quotes = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $summary = ['total' => 0, 'accepted' => 0, 'total_val' => 0.0];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('quotes')) {
                $query = Quote::where('workspace_id', $workspace->id)
                    ->with(['lead:id,first_name,last_name,email,company_id', 'lead.company:id,name', 'company:id,name', 'deal:id,title']);

                if ($search = $request->input('search')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('quote_number', 'like', "%{$search}%")
                          ->orWhereHas('lead', fn($l) => $l->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                          ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
                    });
                }

                if ($status = $request->input('status')) {
                    $query->where('status', $status);
                }

                $quotes = $query->latest()->paginate(15)->withQueryString();

                $summary = [
                    'total'     => Quote::where('workspace_id', $workspace->id)->count(),
                    'accepted'  => Quote::where('workspace_id', $workspace->id)->where('status', 'accepted')->count(),
                    'total_val' => (float) Quote::where('workspace_id', $workspace->id)->whereIn('status', ['sent', 'accepted', 'invoiced'])->sum('total'),
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('QuoteController index failed: ' . $e->getMessage());
        }

        return Inertia::render('Quotes/Index', [
            'quotes'  => $quotes,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $nextNumber = 'QUO-' . strtoupper(Str::random(6));

        $products = collect();
        $leads = collect();
        $companies = collect();
        $deals = collect();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
                $products = Product::where('workspace_id', $workspace->id)->where('is_active', true)->select('id', 'name', 'price', 'type')->get();
            }
        } catch (\Throwable $e) {}

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('leads')) {
                $leads = Lead::where('workspace_id', $workspace->id)->select('id', 'first_name', 'last_name', 'email', 'company_id')->with('company:id,name')->limit(100)->get();
            }
        } catch (\Throwable $e) {}

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
                $companies = Company::where('workspace_id', $workspace->id)->select('id', 'name')->limit(100)->get();
            }
        } catch (\Throwable $e) {}

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('deals')) {
                $deals = Deal::where('workspace_id', $workspace->id)->select('id', 'title', 'value')->limit(100)->get();
            }
        } catch (\Throwable $e) {}

        return Inertia::render('Quotes/Create', [
            'nextNumber' => $nextNumber,
            'products'   => $products,
            'leads'      => $leads,
            'companies'  => $companies,
            'deals'      => $deals,
            'preselected' => [
                'lead_id'    => $request->input('lead_id'),
                'deal_id'    => $request->input('deal_id'),
                'company_id' => $request->input('company_id'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $validated = $request->validate([
            'quote_number'    => 'required|string|max:50',
            'lead_id'         => 'nullable|exists:leads,id',
            'company_id'      => 'nullable|exists:companies,id',
            'deal_id'         => 'nullable|exists:deals,id',
            'status'          => 'required|in:draft,sent,accepted,declined,invoiced',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'issue_date'      => 'nullable|date',
            'expiry_date'     => 'nullable|date',
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

        $quote = Quote::create([
            'workspace_id'    => $workspace->id,
            'quote_number'    => $validated['quote_number'],
            'lead_id'         => $validated['lead_id'],
            'company_id'      => $validated['company_id'],
            'deal_id'         => $validated['deal_id'],
            'user_id'         => $request->user()->id,
            'status'          => $validated['status'],
            'subtotal'        => $subtotal,
            'tax_rate'        => $taxRate,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discount,
            'total'           => $total,
            'currency'        => 'USD',
            'issue_date'      => $validated['issue_date'],
            'expiry_date'     => $validated['expiry_date'],
            'terms'           => $validated['terms'],
            'notes'           => $validated['notes'],
        ]);

        foreach ($validated['items'] as $item) {
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'product_id'  => $item['product_id'] ?? null,
                'item_name'   => $item['item_name'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total'       => ($item['quantity'] * $item['unit_price']),
            ]);
        }

        return redirect()->route('quotes.show', $quote->id)->with('success', 'Quote created successfully.');
    }

    public function show(Request $request, Quote $quote): Response
    {
        $workspace = $this->getWorkspace($request);
        abort_if($quote->workspace_id !== $workspace->id, 403);

        $quote->load([
            'items.product',
            'lead:id,first_name,last_name,email,phone,company_id',
            'lead.company:id,name',
            'company:id,name,domain,website,phone,address,city,country',
            'deal:id,title,value',
            'user:id,name,email',
            'workspace:id,name'
        ]);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $workspace = $this->getWorkspace($request);
        abort_if($quote->workspace_id !== $workspace->id, 403);

        $request->validate([
            'status' => 'required|in:draft,sent,accepted,declined,invoiced',
        ]);

        $updates = ['status' => $request->status];
        if ($request->status === 'accepted' && !$quote->accepted_at) {
            $updates['accepted_at'] = now();
        }

        $quote->update($updates);

        return back()->with('success', "Quote marked as {$request->status}.");
    }

    public function convertToInvoice(Request $request, Quote $quote)
    {
        $workspace = $this->getWorkspace($request);
        abort_if($quote->workspace_id !== $workspace->id, 403);

        $invoiceNumber = 'INV-' . strtoupper(Str::random(6));

        $invoice = Invoice::create([
            'workspace_id'    => $workspace->id,
            'invoice_number'  => $invoiceNumber,
            'quote_id'        => $quote->id,
            'lead_id'         => $quote->lead_id,
            'company_id'      => $quote->company_id,
            'deal_id'         => $quote->deal_id,
            'user_id'         => $request->user()->id,
            'status'          => 'sent',
            'subtotal'        => $quote->subtotal,
            'tax_rate'        => $quote->tax_rate,
            'tax_amount'      => $quote->tax_amount,
            'discount_amount' => $quote->discount_amount,
            'total'           => $quote->total,
            'amount_paid'     => 0,
            'currency'        => $quote->currency,
            'issue_date'      => now()->toDateString(),
            'due_date'        => now()->addDays(14)->toDateString(),
            'terms'           => $quote->terms,
            'notes'           => $quote->notes,
        ]);

        foreach ($quote->items as $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'product_id'  => $item->product_id,
                'item_name'   => $item->item_name,
                'description' => $item->description,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'total'       => $item->total,
            ]);
        }

        $quote->update(['status' => 'invoiced']);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Quote successfully converted to Invoice ' . $invoiceNumber);
    }
}
