<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::pending()->count(),
            'total_products' => Product::count(),
            'total_users' => User::where('role', 'client')->count(),
            'total_categories' => Category::count(),
            'total_revenue' => Order::completed()->sum('total_amount'),

            // Commandes actuellement en cours de livraison
            'in_delivery_orders' => Order::where(
                'tracking_status',
                'en_cours_de_livraison'
            )->count(),

            // Nouvelles statistiques du tableau de bord
            'today_orders' => Order::whereDate('created_at', today())->count(),

            // Produits totalement en rupture de stock
            'out_of_stock_products' => Product::where('stock', 0)->count(),

            // Produits avec un stock faible : entre 1 et 5
            'low_stock_products' => Product::whereBetween('stock', [1, 5])->count(),
        ];

        $recentOrders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        // Calcul des ventes par catégorie pour le graphique
        $categoriesSales = \App\Models\Category::withCount([
            'products as sales_count' => function ($query) {
                $query->join('order_items','products.id','=','order_items.product_id')
                ->join('orders','orders.id','=','order_items.order_id')
                ->where('orders.status', 'completed');
            }
        ])->get();

        // Préparation des données pour JavaScript
        $chartLabels = $categoriesSales->pluck('name')->toArray();
        $chartData = $categoriesSales->pluck('sales_count')->toArray();

        return view('admin.dashboard', compact('stats','recentOrders','chartLabels','chartData'));
    }
}