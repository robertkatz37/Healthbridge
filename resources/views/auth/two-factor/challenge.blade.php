<x-guest-layout title="Two-Factor Authentication">
    <div class="text-center mb-2">
        <div style="width:56px;height:56px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="var(--hb-emerald-700)" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h2 class="hb-auth-title">Two-factor authentication</h2>
        <p class="hb-auth-subtitle">Enter the 6-digit code from your authenticator app</p>
    </div>

    @if ($errors->any())
        <div class="hb-alert hb-alert-danger mb-3">
            {{ $errors->first() }}
        </div>
    @endif

    <div x-data="{ mode: 'code' }">

        {{-- TOTP Code Mode --}}
        <div x-show="mode === 'code'">
            <form method="POST" action="{{ route('two-factor.challenge') }}" x-data="otpInput()">
                @csrf
                <input type="hidden" name="code" x-ref="hidden">

                <div class="hb-otp-inputs" x-init="init()">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1" data-otp
                               autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                               {{ $i === 0 ? 'autofocus' : '' }}>
                    @endfor
                    <input type="hidden" name="code">
                </div>

                <button type="submit" class="hb-btn-primary mt-3">
                    Verify Code
                </button>
            </form>

            <div class="text-center mt-3">
                <button class="hb-link" style="background:none;border:none;font-size:0.875rem;" @click="mode = 'recovery'">
                    Use a recovery code instead
                </button>
            </div>
        </div>

        {{-- Recovery Code Mode --}}
        <div x-show="mode === 'recovery'" style="display:none;">
            <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                Enter one of your 8-character recovery codes. Each code can only be used once.
            </p>
            <form method="POST" action="{{ route('two-factor.challenge') }}">
                @csrf
                <div class="mb-4">
                    <label for="recovery_code" class="hb-form-label">Recovery code</label>
                    <input id="recovery_code" type="text" name="recovery_code"
                           class="hb-form-control @error('recovery_code') is-invalid @enderror"
                           placeholder="XXXXX-XXXXX" autofocus autocomplete="off"
                           style="text-transform:uppercase;letter-spacing:0.1em;">
                    @error('recovery_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="hb-btn-primary">Use Recovery Code</button>
            </form>

            <div class="text-center mt-3">
                <button class="hb-link" style="background:none;border:none;font-size:0.875rem;" @click="mode = 'code'">
                    Use authenticator app instead
                </button>
            </div>
        </div>

    </div>

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hb-link" style="background:none;border:none;font-size:0.8rem;color:var(--hb-gray-600);">
                Not you? Sign out
            </button>
        </form>
    </div>
</x-guest-layout>
