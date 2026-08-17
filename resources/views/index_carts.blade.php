@include('layouts.header')

@section('content')

<div class="container py-5">

    <h2 class="mb-4">Mon panier</h2>

    <div class="row">

        <!-- LISTE PRODUITS -->
        <div class="col-lg-8">

           @forelse($cart as $id => $item)

            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body d-flex align-items-center justify-content-between">

                    <!-- IMAGE + INFO -->
                    <div class="d-flex align-items-center gap-3">

                        {{-- Utilisation des crochets pour les tableaux de session --}}
                        <img src="{{ asset($item['image']) }}"
                             width="80"
                             height="80"
                             style="object-fit:cover;border-radius:10px;">

                        <div>
                            <h6 class="mb-1">{{ $item['name'] }}</h6>

                            <small class="text-muted">
                                {{ number_format($item['price'],0,',',' ') }} FCFA
                            </small>
                        </div>

                    </div>

                    <!-- QUANTITÉ -->
                    {{-- On passe l'identifiant ($id) du produit dans la session pour la route --}}
                    <form action="{{ route('cart.update', $id) }}" method="POST" class="d-flex align-items-center gap-2">
                        @csrf
                        @method('PATCH')

                        <input type="number"
                               name="quantity"
                               value="{{ $item['quantity'] }}"
                               min="1"
                               class="form-control"
                               style="width:80px;">
                            
                        <button class="btn btn-dark btn-sm">
                           <a href="{{ route('catalogue') }}">OK</a> 
                        </button>
                    </form>

                    <!-- SUBTOTAL -->
                    <div class="fw-bold">
                        {{ number_format($item['price'] * $item['quantity'],0,',',' ') }} FCFA
                    </div>

                    <!-- DELETE -->
                    {{-- Ici aussi, on utilise l'identifiant $id pour cibler le produit à retirer --}}
                    <form action="{{ route('cart.destroy', $id) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button class="btn btn-danger btn-sm">
                            🗑
                        </button>
                    </form>

                </div>
            </div>

            @empty

            <div class="alert alert-warning">
                Votre panier est vide
            </div>

            @endforelse

        </div>

        <!-- RÉCAPITULATIF -->
        <div class="col-lg-4">

            <div class="card shadow border-0 p-3">

                <h5>Récapitulatif</h5>

                <hr>

                @php
                    // Calcul dynamique du sous-total depuis le tableau de session
                    $sousTotal = array_sum(array_map(function($item) {
                        return $item['price'] * $item['quantity'];
                    }, $cart));
                @endphp

                <div class="d-flex justify-content-between">
                    <span>Sous-total</span>
                    <strong>{{ number_format($sousTotal,0,',',' ') }} FCFA</strong>
                </div>

                 <div class="alert alert-info mt-3">
                <strong>Conditions de livraison</strong>
                <hr>
                🚚 Dakar : <strong>2 000 FCFA</strong><br>
                🚚 Hors de Dakar : <strong>2 500 FCFA</strong>
            </div>
                <hr>

                <a href="{{ route('checkout.index') }}" class="btn btn-dark w-100 mt-3">
                    Passer au paiement
                </a>

            </div>

        </div>

    </div>

</div>

@include('layouts.footer')