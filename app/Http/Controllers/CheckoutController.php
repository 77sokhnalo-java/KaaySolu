<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class CheckoutController extends Controller
{
    public function index()
{
    if (!Auth::check()) {

        return redirect()
            ->route('login')
            ->with('error', 'Veuillez vous connecter avant de passer au paiement.');

    }

       $cart = session()->get('cart', []);

    if (empty($cart)) {

        return redirect()
            ->route('cart.index')
            ->with('error', 'Votre panier est vide.');
    }

    return view('checkout.index', compact('cart'));
}

   public function store(Request $request)
    {
        $request->validate([
    'customer_name' => 'required|string|max:255',
    'phone' => 'required|string|max:20',
    'city' => 'required|string|max:100',
    'address' => 'required|string',
    'payment_method' => 'required|string',
]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        // 1. Calcul du total pour la commande

         $livraison = strtolower(trim($request->city)) == 'dakar' ? 2000 : 2500;

        $totalProduits = array_sum(array_map(function ($item) {
    return $item['price'] * $item['quantity'];
            }, $cart));

        $total = $totalProduits + $livraison;
         
       
        // 2. Création de la commande
        $order = Order::create([
            'order_number'   => 'CMD-' . strtoupper(Str::random(8)),
            'user_id'        => Auth::id(),
            'customer_name' => $request->customer_name,
            'customer_email' => Auth::user()->email,
            'phone'          => $request->phone,
            'city'           => $request->city,
             'address'        => $request->address,
            'payment_method' => $request->payment_method,
           'total' => $total,
            'status'         => 'En attente',
        ]);

        // 3. Mise à jour du stock et ajout des articles à la commande
        foreach ($cart as $id => $item) {
            $product = Product::find($id);

            if ($product) {
                // Diminution du stock
                $product->stock -= $item['quantity'];
                $product->save();

                // Optionnel : Enregistrer les articles dans une table order_items
                // $order->items()->create([...]);
            }
        }

        // 4. Vider le panier en session
        session()->forget('cart');

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Commande créée avec succès.');
    }
}