<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Récupère les articles du panier selon le statut de connexion.
     */
    private function getCartItemsData()
    {
        if (Auth::check()) {
            // Utilisateur connecté : on prend la base de données
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $items = $cart->items()->with('product')->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                ];
            });
            $total = $cart->total();
        } else {
            // Invité : on prend la session
            $sessionCart = Session::get('cart', []);
            $items = collect();
            $total = 0;

            foreach ($sessionCart as $productId => $quantity) {
                $product = Product::find($productId);
                if ($product) {
                    $items->push([
                        'id' => $productId, // On utilise l'ID produit comme clé pour les invités
                        'product_id' => $productId,
                        'product' => $product,
                        'quantity' => $quantity,
                    ]);
                    $total += $product->price * $quantity; // Ajustez si votre modèle utilise une autre colonne de prix
                }
            }
        }

        return compact('items', 'total');
    }

    public function index()
    {
        $cartData = $this->getCartItemsData();
        $cartItems = $cartData['items'];
        $total = $cartData['total'];

        return view('client.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request, $id = null)
    {
        // On récupère l'ID soit depuis l'URL (notre bouton d'accueil), soit depuis le formulaire classique
        $productId = $id ?? $request->input('product_id');
        $quantity = $request->input('quantity', 1);

        if (!$productId) {
            return back()->with('error', 'Produit introuvable.');
        }

        $product = Product::findOrFail($productId);

        if (!$product->hasEnoughStock($quantity)) {
            return back()->with('error', 'Stock insuffisant.');
        }

        if (Auth::check()) {
            // Mode Connecté
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $cart->addProduct($product, $quantity);
        } else {
            // Mode Invité : Stockage en session
            $cart = Session::get('cart', []);
            
            if (isset($cart[$product->id])) {
                $cart[$product->id] += $quantity;
            } else {
                $cart[$product->id] = $quantity;
            }

            Session::put('cart', $cart);
        }

        // Reponse AJAX
        if ($request->expectsJson()) {

            if (Auth::check()) {

                $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
                $cartCount = $cart->items()->sum('quantity');

            } else {

                $cartCount = array_sum(Session::get('cart', []));

            }

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au panier.',
                'cartCount' => $this->getCartItemsData()['items']->sum('quantity')
            ]);
                    }
        return back()->with('success', 'Produit ajouté au panier.');
    }

    public function update(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $newQuantity = (int) $validated['quantity'];
        $oldQuantity = 0;

        if (Auth::check()) {

            $cartItem = CartItem::findOrFail($itemId);

            $cart = Cart::firstOrCreate([
                'user_id' => Auth::id()
            ]);

            if ($cartItem->cart_id !== $cart->id) {
                abort(403);
            }

            if (!$cartItem->product->hasEnoughStock($newQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant.'
                ]);
            }

            $oldQuantity = $cartItem->quantity;

            $cartItem->update([
                'quantity' => $newQuantity
            ]);

            $product = $cartItem->product;

        } else {

            $product = Product::findOrFail($itemId);

            if (!$product->hasEnoughStock($newQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant.'
                ]);
            }

            $cart = Session::get('cart', []);

            if (isset($cart[$itemId])) {

                $oldQuantity = $cart[$itemId];

                $cart[$itemId] = $newQuantity;

                Session::put('cart', $cart);

            }

        }

        $message = $newQuantity > $oldQuantity
            ? 'Produit ajouté avec succès.'
            : 'La quantité a été mise à jour.';

        $cartCount = $this->getCartItemsData()['items']->sum('quantity');

        $subtotal = $product->price * $newQuantity;

        $total = $this->getCartItemsData()['total'];

        return response()->json([
            'success'   => true,
            'message'   => $message,
            'quantity'  => $newQuantity,
            'subtotal'  => $subtotal,
            'total'     => $total,
            'cartCount' => $cartCount
        ]);
    }
    public function remove(Request $request, $itemId)
    {
        if (Auth::check()) {
            // Mode Connecté
            $cartItem = CartItem::findOrFail($itemId);
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            
            if ($cartItem->cart_id !== $cart->id) {
                abort(403);
            }

            $cartItem->delete();
        } else {
            // Mode Invité
            $cart = Session::get('cart', []);
            if (isset($cart[$itemId])) {
                unset($cart[$itemId]);
                Session::put('cart', $cart);
            }
        }

        //return back()->with('success', 'Produit retiré du panier.');
        if ($request->expectsJson()) {

        return response()->json([
            'success' => true,
            'message' => 'Produit retiré du panier.',
            'cartCount' => $this->getCartItemsData()['items']->sum('quantity')
        ]);

        }
        return back()->with('success', 'Produit retiré du panier.');
    }
}
