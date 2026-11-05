<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;

        $query = Product::where('workspace_id', $workspace->id)
            ->with('category:id,name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($cat = $request->input('category_id')) {
            $query->where('category_id', $cat);
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = ProductCategory::where('workspace_id', $workspace->id)->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100',
            'category_id'    => 'nullable|exists:product_categories,id',
            'type'           => 'required|in:service,retainer,lead_package,product',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'cost'           => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'billing_period' => 'nullable|string|max:50',
            'is_active'      => 'boolean',
        ]);

        $validated['workspace_id'] = $workspace->id;
        $validated['currency'] = $validated['currency'] ?: 'USD';

        Product::create($validated);

        return back()->with('success', 'Product/Package created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($product->workspace_id !== $workspace->id, 403);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100',
            'category_id'    => 'nullable|exists:product_categories,id',
            'type'           => 'required|in:service,retainer,lead_package,product',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'cost'           => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:10',
            'billing_period' => 'nullable|string|max:50',
            'is_active'      => 'boolean',
        ]);

        $product->update($validated);

        return back()->with('success', 'Product/Package updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($product->workspace_id !== $workspace->id, 403);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function storeCategory(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ProductCategory::create([
            'workspace_id' => $workspace->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Category added.');
    }
}
