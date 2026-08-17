<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Affiche l'état actuel du stock.
     */
    public function index()
    {
        $products = Product::with('category')
            ->orderBy('stock', 'asc')
            ->paginate(20);

        return view('admin.stock.index', compact('products'));
    }
        /**
 * Affiche l'historique des mouvements de stock.
 */
    public function movements()
    {
        $movements = StockMovement::with(['product', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.stock.movements', compact('movements'));
    }
        /**
     * Affiche le formulaire d'approvisionnement.
     */
    public function create(Product $product)
    {
        return view('admin.stock.create', compact('product'));
    }
    // Enregistre un approvisionnement.
public function store(Request $request, Product $product)
{
    $validated = $request->validate([
        'quantity' => ['required', 'integer', 'min:1'],
        'reason' => ['nullable', 'string', 'max:255'],
    ]);

    try {

        $stockAfter = 0;

        DB::transaction(function () use ($validated, $product, &$stockAfter) {

            // Stock avant l'approvisionnement
            $stockBefore = $product->stock;

            // Quantité ajoutée
            $quantity = $validated['quantity'];

            // Nouveau stock
            $stockAfter = $stockBefore + $quantity;

            // Mise à jour du produit
            $product->update([
                'stock' => $stockAfter,
            ]);

            // Enregistrement du mouvement
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'approvisionnement',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $validated['reason'] ?? 'Approvisionnement du stock',
            ]);
        });

        // Réponse JSON pour AJAX
        return response()->json([
            'success' => true,
            'message' => 'Stock approvisionné avec succès.',
            'stock' => $stockAfter,
        ], 200);

    } catch (\Throwable $e) {

        // Réponse JSON en cas d'erreur
        return response()->json([
            'success' => false,
            'message' => 'Impossible d’enregistrer l’approvisionnement.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}