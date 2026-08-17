<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    /**
     * Récupère ou crée le panier de l'utilisateur connecté ou invité.
     */
    private function getCartData()
    {
        $items = collect();
        $total = 0;

        if (Auth::check()) {
            // Mode Connecté : On récupère depuis la base de données
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            $cartItems = $cart->items()->with('product')->get();
            $total = $cart->total();
            
            foreach ($cartItems as $item) {
                $items->push([
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                ]);
            }
        } else {
            // Mode Invité : On récupère depuis la session temporaire
            $sessionCart = Session::get('cart', []);
            foreach ($sessionCart as $productId => $quantity) {
                $product = Product::find($productId);
                if ($product) {
                    $items->push([
                        'product_id' => $productId,
                        'product' => $product,
                        'quantity' => $quantity,
                    ]);
                    $total += $product->price * $quantity;
                }
            }
        }

        return compact('items', 'total');
    }
    
    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter ou vous inscrire pour passer votre commande.');
        }

        $cartData = $this->getCartData();
        $cartItems = $cartData['items'];
        $total = $cartData['total'];

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        return view('client.orders.checkout', compact('cartItems', 'total'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour valider la commande.');
        }

        // 1. Validation alignée sur les nouveaux champs du formulaire Jumia
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'in:wave,orange_money,cash'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cartData = $this->getCartData();
        $cartItems = $cartData['items'];
        $subtotal = $cartData['total'];

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        // 2. Ajout strict des 2 000 F de frais logistiques au montant de la facture
        $deliveryFee = 2000;
        $totalWithDelivery = $subtotal + $deliveryFee;

        // Préparation d'une note propre qui inclut le nom saisi par sécurité
        $finalNotes = "Destinataire: " . $validated['customer_name'];
        if (!empty($validated['notes'])) {
            $finalNotes .= " | Notes: " . $validated['notes'];
        }

        // 3. Sauvegarde dans la base de données
        $order = Order::create([
            'user_id' => Auth::id(),
            'shipping_address' => $validated['shipping_address'], // Quartier
            'shipping_city' => $validated['shipping_city'],       // Ville Saisie
            'shipping_phone' => $validated['shipping_phone'],     // Téléphone Mobile
            'total_amount' => $totalWithDelivery,                 // Total calculé avec livraison
            'payment_method' => $validated['payment_method'],     // wave, orange_money, cash
            'status' => 'pending',
            'notes' => $finalNotes,
        ]);
                // METTRE À JOUR LE PROFIL DE L'UTILISATEUR S'IL N'A PAS ENCORE D'ADRESSE OU DE VILLE
        $user = Auth::user();
        if (empty($user->address) || empty($user->city)) {
            $user->update([
                'address' => $validated['shipping_address'],
                'city' => $validated['shipping_city'],
            ]);
        }
        // SÉCURITÉ COMPATIBILITÉ : On s'assure que la colonne de suivi existe avant d'enregistrer
        if (\Schema::hasColumn('orders', 'tracking_status')) {
            $order->update(['tracking_status' => 'en_preparation']);
        }

        // Enregistrement des articles de la commande
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['product']->price,
            ]);

            // Diminution du stock du vêtement
            $item['product']->decrement('stock', $item['quantity']);
        }

        // Nettoyage du panier
        $cart = Cart::where('user_id', Auth::id())->first();
        if ($cart) {
            $cart->clear();
        }

        return redirect()->route('orders.show', $order)->with('success', 'Commande passée avec succès !');
    }

    public function index()
    {
        if (!Auth::check()) abort(403);

        $orders = Order::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('client.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if (!Auth::check() || $order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('items.product');

        // Récupération du sous-total pour les calculs d'affichage de la facture
        $total = 0;
        foreach ($order->items as $item) {
            $total += $item->price * $item->quantity;
        }

        return view('client.orders.show', compact('order', 'total'));
    }

    public function confirmReceipt(Request $request, Order $order)
    {
        // Sécurité : On vérifie que c'est bien le propriétaire de la commande qui clique
        if ($order->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // On passe le suivi sur "livre" (Colis reçu)
        $order->update([
            'tracking_status' => 'livre'
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => 'Réception enregistrée.']);
        }

        return back();
    }

}
