<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Agency Onboarding' }} — HealthsBridge</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        body { background: var(--hb-gray-50); font-family: 'Inter', sans-serif; }

        .hb-wizard-header {
            background: var(--hb-emerald-900);
            padding: 1.25rem 0;
        }
        .hb-wizard-logo {
            display: flex; align-items: center; gap: .625rem;
            font-family: 'Fraunces', serif; font-size: 1.1rem; font-weight: 700; color: #fff;
        }
        .hb-wizard-logo span { color: var(--hb-emerald-500); }
        .hb-wizard-logo-mark {
            width: 34px; height: 34px; background: var(--hb-emerald-500);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
        }

        .hb-wizard-progress {
            background: white;
            border-bottom: 1px solid var(--hb-gray-200);
            padding: 1.5rem 0;
        }
        .hb-wizard-steps {
            display: flex; align-items: center; justify-content: center;
            max-width: 720px; margin: 0 auto; padding: 0 1rem;
        }
        .hb-wizard-step {
            display: flex; flex-direction: column; align-items: center; flex: 1; position: relative;
        }
        .hb-wizard-step-circle {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.875rem; font-weight: 700;
            border: 2px solid var(--hb-gray-200); background: white; color: var(--hb-gray-600);
            transition: all 0.2s; z-index: 2;
        }
        .hb-wizard-step.completed .hb-wizard-step-circle {
            background: var(--hb-emerald-700); border-color: var(--hb-emerald-700); color: white;
        }
        .hb-wizard-step.active .hb-wizard-step-circle {
            background: white; border-color: var(--hb-emerald-700); color: var(--hb-emerald-700);
            box-shadow: 0 0 0 4px var(--hb-emerald-100);
        }
        .hb-wizard-step-label {
            font-size: 0.7rem; font-weight: 600; color: var(--hb-gray-600); margin-top: 0.5rem;
            text-align: center; white-space: nowrap;
        }
        .hb-wizard-step.active .hb-wizard-step-label,
        .hb-wizard-step.completed .hb-wizard-step-label { color: var(--hb-gray-900); }
        .hb-wizard-connector {
            position: absolute; top: 18px; left: 50%; width: 100%; height: 2px;
            background: var(--hb-gray-200); z-index: 1;
        }
        .hb-wizard-step.completed .hb-wizard-connector { background: var(--hb-emerald-700); }
        .hb-wizard-step:last-child .hb-wizard-connector { display: none; }

        .hb-wizard-body { max-width: 720px; margin: 0 auto; padding: 2.5rem 1rem 4rem; }
        .hb-wizard-card {
            background: white; border-radius: 1rem; padding: 2rem;
            box-shadow: 0 2px 12px rgba(6,61,46,0.06);
        }
        @media (max-width: 576px) {
            .hb-wizard-card { padding: 1.5rem 1.25rem; }
            .hb-wizard-step-label { display: none; }
        }
    </style>
</head>
<body>

<div class="hb-wizard-header">
    <div class="container">
        <div class="hb-wizard-logo">
            <div class="hb-wizard-logo-mark">
                <i class="bi bi-heart-pulse-fill text-white" style="font-size:1rem;"></i>
            </div>
            Healths<span>Bridge</span>
        </div>
    </div>
</div>

<div class="hb-wizard-progress">
    <div class="hb-wizard-steps">
        @php
            $steps = [
                1 => ['label' => 'Basic Info', 'icon' => 'bi-building'],
                2 => ['label' => 'Services', 'icon' => 'bi-list-check'],
                3 => ['label' => 'Coverage & Hours', 'icon' => 'bi-geo-alt'],
                4 => ['label' => 'Compliance', 'icon' => 'bi-patch-check'],
                5 => ['label' => 'Review & Submit', 'icon' => 'bi-check2-circle'],
            ];
        @endphp
        @foreach($steps as $num => $step)
            <div class="hb-wizard-step {{ $num < $currentStep ? 'completed' : ($num === $currentStep ? 'active' : '') }}">
                <div class="hb-wizard-connector"></div>
                <div class="hb-wizard-step-circle">
                    @if($num < $currentStep)
                        <i class="bi bi-check-lg"></i>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <div class="hb-wizard-step-label">{{ $step['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="hb-wizard-body">
    @if(session('status') === 'step-saved')
        <div class="hb-alert hb-alert-success mb-3" data-auto-dismiss="3000">
            <i class="bi bi-check-circle me-2"></i> Progress saved.
        </div>
    @endif

    @if ($errors->any())
        <div class="hb-alert hb-alert-danger mb-3">
            <i class="bi bi-exclamation-circle me-2"></i>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="hb-wizard-card">
        {{ $slot }}
    </div>
</div>

@stack('scripts')
</body>
</html>
