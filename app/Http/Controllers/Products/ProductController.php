<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
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
                session(['current_workspace_id' => $workspace->id]);
                try {
                    $user->setCurrentWorkspace($workspace);
                } catch (\Throwable $e) {
                    // ignore
                }
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('products')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('ProductController ensureTables migration error: ' . $e->getMessage());
            }
        }
    }

    public function index(Request $request): Response
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

        $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $categories = [];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
                $query = Product::where('workspace_id', $workspace->id);
                if (\Illuminate\Support\Facades\Schema::hasTable('product_categories')) {
                    $query->with('category:id,name');
                }

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
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ProductController index failed: ' . $e->getMessage());
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_categories')) {
                $categories = ProductCategory::where('workspace_id', $workspace->id)->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ProductCategory query failed: ' . $e->getMessage());
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

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
        $workspace = $this->getWorkspace($request);
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
        $workspace = $this->getWorkspace($request);
        abort_if($product->workspace_id !== $workspace->id, 403);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function storeCategory(Request $request)
    {
        $workspace = $this->getWorkspace($request);
        $this->ensureTables();

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
