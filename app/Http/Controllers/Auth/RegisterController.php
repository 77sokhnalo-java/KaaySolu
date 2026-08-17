<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('client.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 1. ÉTAPE DE SÉCURITÉ : On sauvegarde temporairement le panier de l'invité avant l'inscription
        $sessionCart = Session::get('cart', []);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
        ]);

        Auth::login($user);

        // 2. ÉTAPE DE SYNCHRONISATION : Transfert des articles de la session vers la BDD
        if (!empty($sessionCart)) {
            // On crée le panier officiel en BDD pour ce nouvel utilisateur
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // On y ajoute chaque produit de la session
            foreach ($sessionCart as $productId => $quantity) {
                $product = Product::find($productId);
                if ($product) {
                    $cart->addProduct($product, $quantity);
                }
            }

            // On nettoie le panier de session devenu inutile
            Session::forget('cart');
        }

        // 3. REDIRECTION FLUIDE VERS LA VALIDATION DE COMMANDE
        return redirect()->intended(route('home'));
    }
}
