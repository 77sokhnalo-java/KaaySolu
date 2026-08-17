<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
// Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;


/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES
|--------------------------------------------------------------------------
*/

// =========================
// Accueil
// =========================
Route::get('/', function () {

    $products = Product::latest()->take(4)->get();

    return view('welcome', compact('products'));

})->name('home');


// =========================
// Authentification
// =========================
Route::controller(AuthController::class)->group(function () {

    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login');

    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'store');

    Route::post('/logout', function () {

    Auth::logout();

    request()->session()->invalidate();

    request()->session()->regenerateToken();

    return redirect('/');

})->name('logout');
});


// =========================
// Catalogue
// =========================
Route::controller(ProductController::class)->group(function () {

    Route::get('/catalogue', 'catalogue')
        ->name('catalogue');

    Route::get('/catalogue/{product}', 'show')
        ->name('catalogue.show');
});


// =========================
// Catégories
// =========================
Route::get('/categories', [CategoryController::class, 'index'])
    ->name('categories.index');


/*
|--------------------------------------------------------------------------
| ROUTES CLIENT (connexion obligatoire)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Tableau de bord client
    Route::get('/mon-compte', function () {

        return view('client.dashboard');

    })->name('client.dashboard');

    // Paiement
    Route::get('/paiement', [CheckoutController::class, 'index'])
        ->name('checkout.index');

    Route::post('/paiement', [CheckoutController::class, 'store'])
        ->name('checkout.store');


    // Commandes
    Route::get('/mes-commandes/{order}', [OrderController::class, 'show'])
        ->name('orders.show');
});


/*
|--------------------------------------------------------------------------
| ROUTES ADMIN
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
     ->middleware(['auth','admin'])
    ->group(function () {
    
    Route::get('/admin', [
    DashboardController::class,
    'index'
])->name('admin.dashboard');

        // Dashboard
        Route::get('/dashboard', function () {

            $products = Product::latest()->get();

            $totalProducts = Product::count();

            $lowStock = Product::where('stock', '<=', 10)->count();

            $entriesMonth = Product::whereMonth('created_at', now()->month)->count();

            $exitsMonth = 0;

            return view('admin.dashboard', compact(
                'products',
                'totalProducts',
                'lowStock',
                'entriesMonth',
                'exitsMonth'
            ));

        })->name('dashboard');


        // Produits
// Produits Admin

Route::get('/products', [ProductController::class, 'adminIndex'])
    ->name('products.index');

Route::get('/products/create', [ProductController::class, 'create'])
    ->name('products.create');

Route::post('/products', [ProductController::class, 'store'])
    ->name('products.store');

Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
    ->name('products.edit');

Route::put('/products/{product}', [ProductController::class, 'update'])
    ->name('products.update');

Route::delete('/products/{product}', [ProductController::class, 'destroy'])
    ->name('products.destroy');
        // Catégories
        Route::get('/categories', [CategoryController::class, 'adminIndex'])
            ->name('categories.index');

        Route::resource('categories', CategoryController::class)
            ->except('index');

        Route::patch(
            '/categories/{category}/toggle',
            [CategoryController::class, 'toggle']
        )->name('categories.toggle');


        // Commandes
        Route::resource('commandes', AdminOrderController::class);

        Route::patch(
            '/commandes/{commande}/status',
            [AdminOrderController::class, 'status']
        )->name('commandes.status');


        // Utilisateurs
        Route::resource('utilisateurs', UserController::class);

        Route::patch(
            '/utilisateurs/{utilisateur}/toggle',
            [UserController::class, 'toggle']
        )->name('utilisateurs.toggle');
    }); 


    // Panier
    Route::get('/panier', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/panier/{product}', [CartController::class, 'add'])
        ->name('cart.add');

    Route::patch('/panier/{item}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/panier/{item}', [CartController::class, 'destroy'])
        ->name('cart.destroy');

        //lien footer
Route::get('/promotions', [ProductController::class, 'promotions'])
    ->name('promotions');  
    Route::get('/a-propos', function () {
    return view('pages.about');
})->name('about');                                                   
Route::get('/livraison', function () {
    return view('livraison');
})->name('livraison');
Route::get('/conditions-generales', function () {
    return view('pages.conditions');
})->name('conditions');
Route::get('/faq', function () {
    return view('pages.faq');
})->name('faq');