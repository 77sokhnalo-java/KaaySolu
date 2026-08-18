<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. ÉTAPE DE SÉCURITÉ : On sauvegarde temporairement le panier de l'invité avant la régénération
        $sessionCart = Session::get('cart', []);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Laravel recrée une session propre et sécurisée pour l'utilisateur connecté
            $request->session()->regenerate();
            $user = Auth::user();

            // 2. ÉTAPE DE SYNCHRONISATION : Si le client avait des articles, on les transfère en BDD
            if (!$user->isAdmin() && !empty($sessionCart)) {
                // On récupère ou crée le panier officiel de ce client en BDD
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);

                // On ajoute chaque produit de la session vers la BDD
                foreach ($sessionCart as $productId => $quantity) {
                    $product = Product::find($productId);
                    if ($product) {
                        $cart->addProduct($product, $quantity);
                    }
                }

                // On nettoie l'ancien panier de session devenu inutile
                Session::forget('cart');
            }

            // 3. REDIRECTION INTELLIGENTE STYLE JUMIA
            return redirect()->intended(
                $user->isAdmin() ? route('admin.dashboard') : route('home')
            );
        }

        return back()->withErrors([
            'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->intended(route('home'));
    }
}
