<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?: 'HealthsBridge' }}</title>

    @if($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    @if($canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    <meta property="og:title" content="{{ $title ?: 'HealthsBridge' }}">
    @if($seoDescription)
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($ogImage)
        <meta property="og:image" content="{{ str_starts_with($ogImage, 'http') ? $ogImage : asset('storage/' . $ogImage) }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?: 'HealthsBridge' }}">
    @if($seoDescription)
        <meta name="twitter:description" content="{{ $seoDescription }}">
    @endif
    @if($ogImage)
        <meta name="twitter:image" content="{{ str_starts_with($ogImage, 'http') ? $ogImage : asset('storage/' . $ogImage) }}">
    @endif

    @foreach($jsonLd as $schema)
        @if($schema)
            <script type="application/ld+json">{!! json_encode($schema) !!}</script>
        @endif
    @endforeach

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body style="background:var(--hb-gray-50);font-family:'Inter',sans-serif;">

@php
    $headerMenu = \App\Models\Menu::where('slug', 'header')->first();
    $headerItems = $headerMenu ? $headerMenu->topLevelItems()->get() : collect();
    $footerMenu = \App\Models\Menu::where('slug', 'footer')->first();
    $footerItems = $footerMenu ? $footerMenu->topLevelItems()->get() : collect();
@endphp

<nav class="navbar navbar-expand-lg" style="background:var(--hb-emerald-900);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="{{ route('home') }}"
           style="font-family:'Fraunces',serif;font-size:1.2rem;text-decoration:none;">
            <div style="width:32px;height:32px;background:var(--hb-emerald-500);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-heart-pulse-fill" style="font-size:1rem;color:white;"></i>
            </div>
            Healths<span style="color:var(--hb-emerald-500);">Bridge</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" style="border-color:rgba(255,255,255,0.3);">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="publicNav">
            @if($headerItems->isNotEmpty())
                <ul class="navbar-nav me-auto ms-lg-4">
                    @foreach($headerItems as $item)
                        @if($item->children->isNotEmpty())
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-white-50" href="#" role="button" data-bs-toggle="dropdown">{{ $item->label }}</a>
                                <ul class="dropdown-menu">
                                    @foreach($item->children as $child)
                                        <li><a class="dropdown-item" href="{{ $child->resolved_url }}" target="{{ $child->target }}">{{ $child->label }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="{{ $item->resolved_url }}" target="{{ $item->target }}">{{ $item->label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
            <form action="{{ route('search') }}" method="GET" class="d-flex me-3" style="max-width:220px;">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Search..." value="{{ request('q') }}">
            </form>
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
    </div>
</nav>

@if(!empty($breadcrumbItems))
    <div style="background:white;border-bottom:1px solid var(--hb-gray-200);">
        <div class="container py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size:0.8rem;">
                    @foreach($breadcrumbItems as $index => $item)
                        @if($index === count($breadcrumbItems) - 1)
                            <li class="breadcrumb-item active">{{ $item['label'] }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $item['url'] }}" class="hb-link">{{ $item['label'] }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
@endif

<main class="container py-4">
    {{ $slot }}
</main>

<footer style="background:var(--hb-emerald-900);color:rgba(255,255,255,0.7);margin-top:3rem;">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 text-white fw-bold mb-2" style="font-family:'Fraunces',serif;font-size:1.1rem;">
                    <i class="bi bi-heart-pulse-fill" style="color:var(--hb-emerald-500);"></i>
                    Healths<span style="color:var(--hb-emerald-500);">Bridge</span>
                </div>
                <p style="font-size:0.85rem;">Connecting families with trusted senior care agencies nationwide.</p>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach($footerItems as $item)
                        <div class="col-6 col-md-3">
                            <div class="fw-bold text-white mb-2" style="font-size:0.85rem;">{{ $item->label }}</div>
                            <ul class="list-unstyled" style="font-size:0.8rem;">
                                @foreach($item->children as $child)
                                    <li class="mb-1"><a href="{{ $child->resolved_url }}" target="{{ $child->target }}" style="color:rgba(255,255,255,0.7);text-decoration:none;">{{ $child->label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,0.15);">
        <div style="font-size:0.75rem;">&copy; {{ now()->year }} HealthsBridge. All rights reserved.</div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
