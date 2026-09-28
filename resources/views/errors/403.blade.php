<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Unauthorized — HealthsBridge</title>
    
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body style="background:var(--hb-gray-50);font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div style="max-width:480px;width:100%;text-align:center;padding:2rem;">

        <div style="width:80px;height:80px;background:var(--hb-emerald-100);border-radius:50%;
                    display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
            <i class="bi bi-shield-x" style="font-size:2rem;color:var(--hb-emerald-700);"></i>
        </div>

        <h1 style="font-family:'Fraunces',serif;font-size:1.75rem;font-weight:700;
                   color:var(--hb-gray-900);margin-bottom:0.5rem;">
            Access Denied
        </h1>

        <p style="color:var(--hb-gray-600);font-size:0.9375rem;margin-bottom:1.5rem;line-height:1.6;">
            You do not have permission to access this page.
            If you believe this is a mistake, please contact your administrator.
        </p>

        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('dashboard') }}" class="hb-btn-primary" style="width:auto;padding:0.65rem 1.5rem;text-decoration:none;">
                <i class="bi bi-house me-2"></i> Go to Dashboard
            </a>
            <a href="javascript:history.back()" class="hb-btn-outline" style="padding:0.65rem 1.5rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i> Go Back
            </a>
        </div>

        <p style="margin-top:2rem;font-size:0.75rem;color:var(--hb-gray-600);">
            Error 403 &mdash; HealthsBridge
        </p>
    </div>
</body>
</html>
