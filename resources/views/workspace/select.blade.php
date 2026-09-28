<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Select Workspace — HealthsBridge</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>

<div class="hb-auth-wrapper">
    <div class="hb-auth-card" style="max-width:640px;">

        <div class="hb-auth-logo">
            <div class="hb-logo-mark">
                <i class="bi bi-heart-pulse-fill text-white" style="font-size:1.1rem;"></i>
            </div>
            <div class="hb-logo-text">
                Healths<span>Bridge</span>
            </div>
        </div>

        <div class="text-center mb-4">
            <h2 class="hb-auth-title">Select a Workspace</h2>
            <p class="hb-auth-subtitle">
                Your account has access to multiple workspaces.
                Choose where you'd like to continue.
            </p>
        </div>

        <div class="row g-3">

            @foreach($workspaces as $workspace)

                <div class="col-md-6">

                    <form method="POST"
                          action="{{ route('workspace.enter', $workspace['key']) }}">

                        @csrf

                        <button
                            type="submit"
                            class="w-100 p-0 border-0 bg-transparent text-start"
                            style="cursor:pointer;">

                            <div class="card h-100"
                                 style="
                                    border-radius:1rem;
                                    border:1.5px solid var(--hb-gray-200);
                                    transition:all .2s;
                                 "
                                 onmouseover="
                                    this.style.borderColor='var(--hb-emerald-500)';
                                    this.style.background='var(--hb-emerald-100)';
                                 "
                                 onmouseout="
                                    this.style.borderColor='var(--hb-gray-200)';
                                    this.style.background='white';
                                 ">

                                <div class="card-body p-4 text-center">

                                    <div
                                        style="
                                            width:52px;
                                            height:52px;
                                            background:var(--hb-emerald-100);
                                            border-radius:.875rem;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            margin:0 auto .875rem;
                                        ">

                                        <i class="bi {{ $workspace['icon'] }}"
                                           style="
                                                font-size:1.4rem;
                                                color:var(--hb-emerald-700);
                                           ">
                                        </i>

                                    </div>

                                    <div
                                        style="
                                            font-weight:700;
                                            color:var(--hb-gray-900);
                                            font-size:.95rem;
                                        ">

                                        {{ $workspace['label'] }}

                                    </div>

                                    <div
                                        style="
                                            font-size:.775rem;
                                            color:var(--hb-gray-600);
                                            margin-top:.375rem;
                                            line-height:1.4;
                                        ">

                                        {{ $workspace['description'] }}

                                    </div>

                                </div>

                            </div>

                        </button>

                    </form>

                </div>

            @endforeach

        </div>

        <div class="text-center mt-4">

            <form method="POST" action="{{ route('logout') }}">

                @csrf

                <button
                    type="submit"
                    class="hb-link"
                    style="
                        background:none;
                        border:none;
                        font-size:.8rem;
                        color:var(--hb-gray-600);
                    ">

                    Sign out instead

                </button>

            </form>

        </div>

    </div>
</div>

</body>
</html>