<x-guest-layout title="Forgot Password">
    <div class="text-center mb-2">
        <h2 class="hb-auth-title">Reset your password</h2>
        <p class="hb-auth-subtitle">Enter your email and we'll send you a reset link</p>
    </div>

    @if (session('status'))
        <div class="hb-alert hb-alert-success mb-4">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" x-data="authForm()" @submit="submit" novalidate>
        @csrf
        <div class="mb-4">
            <label for="email" class="hb-form-label">Email address</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="hb-form-control @error('email') is-invalid @enderror"
                       placeholder="you@example.com" required autofocus>
            </div>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="hb-btn-primary mb-3" :class="{ loading: loading }">
            <span x-show="!loading">Send Reset Link</span>
            <span x-show="loading" style="display:none;">Sending...</span>
        </button>
    </form>

    <div class="text-center">
        <a href="{{ route('login') }}" class="hb-link" style="font-size:0.875rem;">
            ← Back to sign in
        </a>
    </div>
</x-guest-layout>
