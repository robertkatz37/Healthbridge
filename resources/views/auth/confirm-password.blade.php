<x-guest-layout title="Confirm Password">
    <div class="text-center mb-2">
        <h2 class="hb-auth-title">Confirm your password</h2>
        <p class="hb-auth-subtitle">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" x-data="authForm()" @submit="submit" novalidate>
        @csrf
        <div class="mb-4">
            <label for="password" class="hb-form-label">Password</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password" type="password" name="password"
                       class="hb-form-control @error('password') is-invalid @enderror"
                       placeholder="Your current password" required autofocus>
            </div>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="hb-btn-primary" :class="{ loading: loading }">
            <span x-show="!loading">Confirm</span>
            <span x-show="loading" style="display:none;">Confirming...</span>
        </button>
    </form>
</x-guest-layout>
