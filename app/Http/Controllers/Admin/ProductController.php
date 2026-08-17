<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
{
    $query = Product::with('category');

    // 🔎 Recherche par nom du produit
    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->where('name', 'like', '%' . $search . '%');
    }

    // 🏷️ Filtre par catégorie
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // Produits les plus récents en premier
    $products = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    // Catégories disponibles pour le filtre
    $categories = Category::where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('admin.products.index', compact(
        'products',
        'categories'
    ));
}
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès.',
                'product' => $product
            ]);
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
'success' => true,
'message' => 'Produit mis à jour avec succès.',
'product' => $product
]);

    }

    public function destroy(Request $request, Product $product)
    {
        $product->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès.'
            ]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}
