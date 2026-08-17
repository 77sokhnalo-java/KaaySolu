@include('layouts.header')

<div class="container py-5">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row">

        <!-- Formulaire -->
        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header">
                    <h4>Paiement</h4>
                </div>

                <div class="card-body">

                    <form action="{{ route('checkout.store') }}" method="POST">

                        @csrf

                        <h5 class="mb-4">Informations de livraison</h5>

                        <div class="mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text"
                                   name="customer_name"
                                   class="form-control"
                                   value="{{ old('customer_name', Auth::user()->name) }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="{{ old('phone') }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text"
                                   name="city"
                                   class="form-control"
                                   value="{{ old('city') }}"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Adresse de livraison</label>
                            <textarea name="address"
                                      class="form-control"
                                      rows="3"
                                      required>{{ old('address') }}</textarea>
                        </div>

                        <hr>

                        <h5 class="mb-3">Mode de paiement</h5>

                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="radio"
                                   name="payment_method"
                                   value="Wave"
                                   required>

                            <label class="form-check-label">
                                💙 Wave
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="radio"
                                   name="payment_method"
                                   value="Orange Money">

                            <label class="form-check-label">
                                🟠 Orange Money
                            </label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input"
                                   type="radio"
                                   name="payment_method"
                                   value="Carte bancaire">

                            <label class="form-check-label">
                                💳 Carte bancaire
                            </label>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            Confirmer la commande
                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- Récapitulatif -->
<div class="col-lg-4">

    <div class="card shadow-sm">

        <div class="card-header">
            <h5>Récapitulatif</h5>
        </div>

        <div class="card-body">

            @php
                $total = 0;
            @endphp

            @foreach($cart as $item)

                @php
                    $ligne = $item['price'] * $item['quantity'];
                    $total += $ligne;
                @endphp

                <div class="d-flex justify-content-between mb-2">

                    <span>
                        {{ $item['name'] }} × {{ $item['quantity'] }}
                    </span>

                    <span>
                        {{ number_format($ligne, 0, ',', ' ') }} FCFA
                    </span>

                </div>

            @endforeach

            <hr>

            <div class="d-flex justify-content-between">
                <span>Sous-total</span>
                <span>{{ number_format($total, 0, ',', ' ') }} FCFA</span>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Conditions de livraison</strong>
                <hr>
                🚚 Dakar : <strong>2 000 FCFA</strong><br>
                🚚 Hors de Dakar : <strong>2 500 FCFA</strong>
            </div>

            <div class="d-flex justify-content-between fw-bold">
                <span>Total (hors frais de livraison)</span>

                <span class="text-primary">
                    {{ number_format($total, 0, ',', ' ') }} FCFA
                </span>
            </div>

        </div>

    </div>

</div>

    </div>

</div>

@include('layouts.footer')