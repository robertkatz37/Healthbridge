<x-guest-layout title="Create Account">
    <div class="text-center mb-2">
        <h2 class="hb-auth-title">Create your account</h2>
        <p class="hb-auth-subtitle">Join HealthsBridge — it's free to get started</p>
    </div>

    @if ($errors->any())
        <div class="hb-alert hb-alert-danger mb-3">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" class="flex-shrink-0 mt-1"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" x-data="{ ...authForm(), ...roleSelector('family'), ...passwordStrength() }" @submit="submit" novalidate>
        @csrf

        {{-- Role Selection --}}
        <div class="mb-4">
            <label class="hb-form-label d-block mb-2">I am registering as a:</label>
            <div class="row g-2">
                <div class="col-6">
                    <label class="hb-role-card d-block" :class="{ selected: selected === 'family' }" @click="select('family')">
                        <input type="radio" name="role" value="family" :checked="selected === 'family'">
                        <div class="hb-role-icon">🏠</div>
                        <div class="hb-role-label">Family</div>
                        <div class="hb-role-desc">Looking for care for a loved one</div>
                    </label>
                </div>
                <div class="col-6">
                    <label class="hb-role-card d-block" :class="{ selected: selected === 'agency_owner' }" @click="select('agency_owner')">
                        <input type="radio" name="role" value="agency_owner" :checked="selected === 'agency_owner'">
                        <div class="hb-role-icon">🏥</div>
                        <div class="hb-role-label">Agency</div>
                        <div class="hb-role-desc">Healthcare agency or provider</div>
                    </label>
                </div>
            </div>
            @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        {{-- Full Name --}}
        <div class="mb-3">
            <label for="name" class="hb-form-label">Full name</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       class="hb-form-control @error('name') is-invalid @enderror"
                       placeholder="Your full name" required autofocus autocomplete="name">
            </div>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="hb-form-label">Email address</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="hb-form-control @error('email') is-invalid @enderror"
                       placeholder="you@example.com" required autocomplete="username">
            </div>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3" x-data="passwordStrength()">
            <label for="password" class="hb-form-label">Password</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password"
                       class="hb-form-control @error('password') is-invalid @enderror"
                       placeholder="Min. 8 characters" required autocomplete="new-password"
                       @input="check($event.target.value)">
                <button type="button" class="hb-input-toggle" @click="togglePassword" tabindex="-1">
                    <svg x-show="!showPassword" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPassword" style="display:none;" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <div class="hb-password-strength" x-show="strength > 0">
                <div class="strength-bar"><div class="strength-fill" :style="`width:${width};background:${color}`"></div></div>
                <div class="strength-text" :style="`color:${color}`" x-text="label"></div>
            </div>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="hb-form-label">Confirm password</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                       class="hb-form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="Repeat password" required autocomplete="new-password">
                <button type="button" class="hb-input-toggle" @click="toggleConfirm" tabindex="-1">
                    <svg x-show="!showConfirm" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showConfirm" style="display:none;" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
        </div>

        {{-- Terms --}}
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1" {{ old('terms') ? 'checked' : '' }}>
                <label class="form-check-label" for="terms" style="font-size:0.8375rem;color:var(--hb-gray-600);">
                    I agree to the <a href="#" class="hb-link">Terms of Service</a> and <a href="#" class="hb-link">Privacy Policy</a>
                </label>
                @error('terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <button type="submit" class="hb-btn-primary" :class="{ loading: loading }">
            <span x-show="!loading">Create Account</span>
            <span x-show="loading" style="display:none;">Creating account...</span>
        </button>
    </form>

    <div class="hb-divider">or</div>

    <div class="text-center" style="font-size:0.875rem;color:var(--hb-gray-600);">
        Already have an account?
        <a href="{{ route('login') }}" class="hb-link ms-1">Sign in</a>
    </div>
</x-guest-layout>
