<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
{
    $query = Product::query();
    $categories = \App\Models\Category::all(); // Requis pour votre menu de filtres à gauche

    // Gestion intelligente du filtre de catégorie (Accepte les ID et le Texte brut)
    if ($request->has('category') && $request->input('category') !== null) {
        $categoryParam = $request->input('category');

        if (is_numeric($categoryParam)) {
            // Si c'est un ID numérique (sélection depuis les filtres du catalogue)
            $query->where('category_id', $categoryParam);
        } else {
            // Si c'est du texte brut (clic depuis la page d'accueil ex: 'homme')
            $query->whereHas('category', function ($q) use ($categoryParam) {
                $q->where('name', 'LIKE', '%' . $categoryParam . '%');
            });
        }
    }

    // Gestion des prix min et max
    if ($request->filled('min_price')) {
        $query->where('price', '>=', $request->input('min_price'));
    }
    if ($request->filled('max_price')) {
        $query->where('price', '<=', $request->input('max_price'));
    }

    // Gestion de la recherche textuelle
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', '%' . $search . '%')
              ->orWhere('description', 'LIKE', '%' . $search . '%');
        });
    }

    $products = $query->latest()->paginate(12);

    return view('client.products.index', compact('products', 'categories'));
}

    public function show($slug)
    {
        $product = Product::where('name', $slug)->firstOrFail();
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return view('client.products.show', compact('product', 'relatedProducts'));
    }
}
