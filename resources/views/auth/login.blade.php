@extends('layouts.client')
@section('title', 'Connexion - KAAY SOLU')
@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <!-- BANDEAU SÉCURISÉ INTELLIGENT SI L'INVITÉ TENTE DE COMMANDER -->
                @if (url()->previous() == route('cart.index') || request()->is('paiement'))
                    <div class="alert alert-warning d-flex align-items-center p-3 mb-4 shadow-sm"
                        style="border-radius: 12px; border-left: 5px solid #ffc107; background-color: #fffdf5;">
                        <i class="bi bi-person-fill-lock text-warning fs-4 me-2"></i>
                        <div class="fw-medium" style="font-size: 0.9rem; color: #664d03;">
                            Veuillez vous connecter ou vous inscrire pour valider votre commande.
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="text-center mb-4 fw-bold text-dark">Connexion</h2>

                        @if ($errors->any())
                            <div class="alert alert-danger py-2 px-3 small mb-3" style="border-radius: 8px;">
                                @foreach ($errors->all() as $error)
                                    <div><i class="bi bi-exclamation-circle me-1"></i> {{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-bold text-secondary">Adresse email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email') }}" required autofocus
                                    style="border-radius: 8px;">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label small fw-bold text-secondary">Mot de passe</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" required style="border-radius: 8px;">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label small text-muted" for="remember">Se souvenir de moi</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-accent py-2.5 fw-bold"
                                    style="border-radius: 8px; letter-spacing: 0.5px;">Se connecter</button>
                            </div>
                        </form>

                        <hr class="my-4 opacity-25">

                        <div class="text-center">
                            <p class="mb-0 small text-muted">Pas encore de compte ? <a href="{{ route('register') }}"
                                    class="text-decoration-none fw-bold" style="color: var(--accent-color);">S'inscrire</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
