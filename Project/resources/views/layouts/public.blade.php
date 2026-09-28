<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HealthsBridge' }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body style="background:var(--hb-gray-50);font-family:'Inter',sans-serif;">

<nav class="navbar navbar-expand-lg" style="background:var(--hb-emerald-900);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="{{ route('agencies.index') }}"
           style="font-family:'Fraunces',serif;font-size:1.2rem;text-decoration:none;">
            <div style="width:32px;height:32px;background:var(--hb-emerald-500);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-heart-pulse-fill" style="font-size:1rem;color:white;"></i>
            </div>
            Healths<span style="color:var(--hb-emerald-500);">Bridge</span>
        </a>
        <div class="d-flex gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="hb-btn-primary" style="width:auto;padding:0.5rem 1.25rem;font-size:0.875rem;text-decoration:none;">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm" style="border-radius:0.625rem;">Sign In</a>
                <a href="{{ route('register') }}" class="hb-btn-primary" style="width:auto;padding:0.5rem 1.25rem;font-size:0.875rem;text-decoration:none;">
                    Get Started
                </a>
            @endauth
        </div>
    </div>
</nav>

<main class="container py-4">
    {{ $slot }}
</main>

@stack('scripts')
</body>
</html>
