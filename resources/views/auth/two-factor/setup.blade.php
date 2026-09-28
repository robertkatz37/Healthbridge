<x-app-layout title="Two-Factor Authentication Setup">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">

                    <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                        <div style="width:44px;height:44px;background:var(--hb-emerald-100);border-radius:0.75rem;display:flex;align-items:center;justify-content:center;">
                            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="var(--hb-emerald-700)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">Two-Factor Authentication</h5>
                            <p class="mb-0" style="font-size:0.875rem;color:var(--hb-gray-600);">
                                @if(auth()->user()->hasTwoFactorEnabled())
                                    2FA is currently <span class="hb-badge-verified">enabled</span>
                                @else
                                    Add an extra layer of security to your account
                                @endif
                            </p>
                        </div>
                    </div>

                    @if(!auth()->user()->hasTwoFactorEnabled())
                        {{-- Step 1: QR Code --}}
                        <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);">Step 1 — Scan this QR code</h6>
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                            Open your authenticator app (Google Authenticator, Authy, 1Password) and scan the QR code below.
                        </p>

                        <div class="hb-qr-box mb-4">
                            <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" class="mb-3">
                            <p style="font-size:0.75rem;color:var(--hb-gray-600);margin-bottom:0.5rem;">
                                Can't scan? Enter this code manually:
                            </p>
                            <code style="font-size:0.875rem;background:var(--hb-gray-200);padding:0.375rem 0.75rem;border-radius:0.5rem;letter-spacing:0.1em;">
                                {{ $secret }}
                            </code>
                        </div>

                        {{-- Step 2: Verify --}}
                        <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);">Step 2 — Verify the code</h6>
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                            Enter the 6-digit code shown in your authenticator app to confirm setup.
                        </p>

                        @if ($errors->any())
                            <div class="hb-alert hb-alert-danger mb-3">{{ $errors->first() }}</div>
                        @endif

                        <form method="POST" action="{{ route('two-factor.enable') }}" x-data="otpInput()">
                            @csrf
                            <div class="hb-otp-inputs" x-init="init()">
                                @for ($i = 0; $i < 6; $i++)
                                    <input type="text" inputmode="numeric" maxlength="1" data-otp {{ $i === 0 ? 'autofocus' : '' }}>
                                @endfor
                                <input type="hidden" name="code">
                            </div>
                            <button type="submit" class="hb-btn-primary mt-3">Enable Two-Factor Authentication</button>
                        </form>
                    @else
                        {{-- Already enabled --}}
                        <div class="hb-alert hb-alert-success mb-4">
                            ✓ Two-factor authentication is active on your account.
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('two-factor.recovery-codes') }}" class="btn btn-outline-primary" style="border-radius:0.75rem;">
                                View Recovery Codes
                            </a>

                            <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary" style="border-radius:0.75rem;">
                                    Regenerate Codes
                                </button>
                            </form>
                        </div>

                        <hr style="border-color:var(--hb-gray-200);margin:1.5rem 0;">
                        <h6 class="fw-bold mb-2 text-danger">Disable Two-Factor Authentication</h6>
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                            Disabling 2FA will make your account less secure. You'll need to confirm your password.
                        </p>
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @if ($errors->has('password'))
                                <div class="hb-alert hb-alert-danger mb-2">{{ $errors->first('password') }}</div>
                            @endif
                            <div class="d-flex gap-2 align-items-end">
                                <div style="flex:1;">
                                    <input type="password" name="password" class="hb-form-control" placeholder="Confirm your password">
                                </div>
                                <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;height:48px;padding:0 1.25rem;">
                                    Disable 2FA
                                </button>
                            </div>
                        </form>
                    @endif

                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('profile.edit') }}" class="hb-link" style="font-size:0.875rem;">← Back to profile</a>
            </div>
        </div>
    </div>
</x-app-layout>
