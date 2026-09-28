<x-guest-layout title="Sign In">
    <div class="text-center mb-2">
        <h2 class="hb-auth-title">Welcome back</h2>
        <p class="hb-auth-subtitle">Sign in to your HealthsBridge account</p>
    </div>

    @if ($errors->any())
        <div class="hb-alert hb-alert-danger mb-3">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" class="flex-shrink-0 mt-1"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" @submit="submit" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="hb-form-label">Email address</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="hb-form-control @error('email') is-invalid @enderror"
                       placeholder="you@example.com" required autofocus autocomplete="username">
            </div>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="hb-form-label d-flex justify-content-between">
                Password
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="hb-link" style="font-weight:400;">Forgot password?</a>
                @endif
            </label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password"
                       class="hb-form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required autocomplete="current-password">
                <button type="button" class="hb-input-toggle" @click="togglePassword" tabindex="-1">
                    <svg x-show="!showPassword" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPassword" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Remember Me --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                       style="border-color:var(--hb-gray-200);width:1rem;height:1rem;">
                <label class="form-check-label" for="remember" style="font-size:0.875rem;color:var(--hb-gray-600);">
                    Remember me for 30 days
                </label>
            </div>
        </div>

        <button type="submit" class="hb-btn-primary" :class="{ loading: loading }">
            <span x-show="!loading">Sign In</span>
            <span x-show="loading" style="display:none;">Signing in...</span>
        </button>
    </form>

    <div class="hb-divider">or</div>

    <div class="text-center" style="font-size:0.875rem;color:var(--hb-gray-600);">
        Don't have an account?
        <a href="{{ route('register') }}" class="hb-link ms-1">Create one free</a>
    </div>
</x-guest-layout>
