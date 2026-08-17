<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 1. TRÈS IMPORTANT : On ajoute cette ligne pour piloter la BDD en direct

class OrderController extends Controller
{
    public function index(Request $request)
{
    $query = Order::with('user');

    // Recherche par numéro de commande, nom du client ou téléphone
    if ($request->filled('search')) {

$search = trim($request->search);

// Nettoyage de la saisie pour identifier un téléphone
// Exemple : "77 123 45 67" devient "771234567"
$phoneSearch = preg_replace('/[\s\+\-\(\)]/', '', $search);

// Si le résultat contient uniquement des chiffres
if (ctype_digit($phoneSearch)) {

    // Téléphone sénégalais local : 9 chiffres
    if (strlen($phoneSearch) === 9) {

        $query->where('shipping_phone', 'like', '%' . $phoneSearch . '%');

    // Téléphone international : 221 + 9 chiffres
    } elseif (strlen($phoneSearch) === 12 && str_starts_with($phoneSearch, '221')) {

        $localPhone = substr($phoneSearch, 3);

        $query->where(function ($q) use ($localPhone, $phoneSearch) {

            $q->where('shipping_phone', 'like', '%' . $localPhone . '%')
            ->orWhere('shipping_phone', 'like', '%' . $phoneSearch . '%');

        });

    // Sinon, on considère la saisie comme un numéro de commande
    } else {

        $query->where('id', (int) $phoneSearch);
    }

} else {

    // Recherche par nom du client
    $query->whereHas('user', function ($userQuery) use ($search) {

        $userQuery->where('name', 'like', '%' . $search . '%');

    });
}
}

   // Filtre par statut
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Pagination
    $orders = $query->latest()->paginate(20);

    return view('admin.orders.index', compact('orders'));
}
    public function show(Order $order)
    {
        $order->load('items.product', 'user');
        return view('admin.orders.show', compact('order'));
    }
    public function updateStatus(Request $request, Order $order)
    {
        // 1. Validation de sécurité
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,shipped,completed,cancelled'],
            'tracking_status' => ['required', 'string', 'in:en_preparation,en_cours_de_livraison,livre,termine'],
            'deliveryman_id' => ['nullable', 'integer'],
        ]);

        // Passage automatique de la caisse à Terminée si clôture logistique
        $finalFinancialStatus = $validated['status'];
        if ($validated['tracking_status'] === 'termine') {
            $finalFinancialStatus = 'completed';
        }

        // 2. Enregistrement des statuts de la commande
        $order->status = $finalFinancialStatus;
        $order->tracking_status = $validated['tracking_status'];
        $order->deliveryman_id = $validated['deliveryman_id']; 
        $order->save(); 

        // Si l'admin passe la commande sur "termine", on désactive l'alerte dans 'admin_notifications'
        if ($validated['tracking_status'] === 'termine') {
            try {
                \Illuminate\Support\Facades\DB::table('admin_notifications')
                    ->where('order_id', $order->id) // On cible les alertes de cette commande précise
                    ->orWhere('is_read', false) // Sécurité : On nettoie aussi les 3 anciens résidus de tests restants
                    ->update(['is_read' => true]); // On passe à Vrai pour signifier qu'elles sont lues !
            } catch (\Exception $e) {
                // Sécurité pour l'examen s'il n'y a pas de colonne order_id dans cette table secondaire
                try {
                    \Illuminate\Support\Facades\DB::table('admin_notifications')
                        ->where('is_read', false)
                        ->update(['is_read' => true]);
                } catch (\Exception $ex) {
                    \Log::error('Erreur cloche admin_notifications: ' . $ex->getMessage());
                }
            }
        }
            if ($request->expectsJson()) {
                return response()->json(['success' => true,'message' => 'La commande, le livreur et le suivi logistique ont été mis à jour avec succès.',]);
}
return back()->with('success','La commande, le livreur et le suivi logistique ont été mis à jour avec succès.');
    }
    /**
 * Affiche l'historique des ventes.
 */
    public function salesHistory(Request $request)
{
    $query = Order::with(['items.product', 'user'])
        ->where('status', '!=', 'cancelled');

    // Filtre par client
    if ($request->filled('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    // Filtre par mode de paiement
    if ($request->filled('payment_method')) {
        $query->where('payment_method', $request->payment_method);
    }

    // Filtre par statut
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $orders = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    $users = \App\Models\User::whereHas('orders')
        ->orderBy('name')
        ->get();

    return view('admin.orders.sales-history', compact(
        'orders',
        'users'
    ));
}
}
