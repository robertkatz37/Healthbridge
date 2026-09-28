<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HealthsBridge — Professional Healthcare Referral Marketplace</title>
    
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body style="background:var(--hb-gray-50);font-family:'Inter',sans-serif;">

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;">
    <div style="text-align:center;max-width:560px;width:100%;">

        <div style="width:72px;height:72px;background:var(--hb-emerald-700);border-radius:18px;
                    display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;
                    box-shadow:0 8px 24px rgba(11,110,79,0.25);">
            <i class="bi bi-heart-pulse-fill" style="font-size:2rem;color:white;"></i>
        </div>

        <h1 style="font-family:'Fraunces',serif;font-size:2.5rem;font-weight:700;
                   color:var(--hb-gray-900);margin-bottom:0.5rem;line-height:1.2;">
            Healths<span style="color:var(--hb-emerald-500);">Bridge</span>
        </h1>

        <p style="color:var(--hb-gray-600);font-size:1.0625rem;margin-bottom:0.5rem;font-weight:500;">
            Professional Healthcare Referral Marketplace
        </p>

        <p style="color:var(--hb-gray-600);font-size:0.9rem;margin-bottom:2rem;line-height:1.6;">
            Connecting families with trusted healthcare agencies through
            intelligent matching and human advisors.
        </p>

        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            @auth
                <a href="{{ route('dashboard') }}" class="hb-btn-primary"
                   style="width:auto;padding:0.7rem 2rem;text-decoration:none;">
                    <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="hb-btn-primary"
                   style="width:auto;padding:0.7rem 2rem;text-decoration:none;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </a>
                <a href="{{ route('register') }}"
                   style="display:inline-flex;align-items:center;padding:0.7rem 2rem;
                          border:2px solid var(--hb-emerald-700);border-radius:0.75rem;
                          color:var(--hb-emerald-700);font-weight:600;text-decoration:none;
                          transition:all 0.2s;">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </a>
            @endauth
        </div>

        <div style="margin-top:3rem;display:flex;justify-content:center;gap:2.5rem;flex-wrap:wrap;">
            @php
                $features = [
                    ['icon' => 'bi-search-heart', 'label' => 'Smart Matching'],
                    ['icon' => 'bi-person-badge', 'label' => 'Human Advisors'],
                    ['icon' => 'bi-shield-check',  'label' => 'Verified Reviews'],
                ];
            @endphp
            @foreach($features as $feature)
                <div style="text-align:center;">
                    <i class="bi {{ $feature['icon'] }}" style="font-size:1.5rem;color:var(--hb-emerald-500);display:block;margin-bottom:0.375rem;"></i>
                    <span style="font-size:0.8rem;font-weight:600;color:var(--hb-gray-600);">{{ $feature['label'] }}</span>
                </div>
            @endforeach
        </div>

    </div>
</div>

</body>
</html>
