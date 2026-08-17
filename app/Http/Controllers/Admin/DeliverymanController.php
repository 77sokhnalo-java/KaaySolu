<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deliveryman;
use Illuminate\Http\Request;

class DeliverymanController extends Controller
{
    /**
     * Affiche la liste des livreurs.
     */
    public function index()
    {
        $deliverymen = Deliveryman::latest()->get();
        return view('admin.deliverymen.index', compact('deliverymen'));
    }

    /**
     * Enregistre un nouveau livreur dans la base de données.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['required', 'string', 'max:50'],
        ]);

        Deliveryman::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'vehicle_type' => $validated['vehicle_type'],
            'is_available' => true,
        ]);

        return redirect()->back()->with('success', 'Livreur ajouté avec succès !');
    }
}
