<x-guest-layout title="Set New Password">
    <div class="text-center mb-2">
        <h2 class="hb-auth-title">Set new password</h2>
        <p class="hb-auth-subtitle">Choose a strong password for your account</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" x-data="authForm()" @submit="submit" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="hb-form-label">Email address</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                       class="hb-form-control @error('email') is-invalid @enderror"
                       required autofocus>
            </div>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="hb-form-label">New password</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password"
                       class="hb-form-control @error('password') is-invalid @enderror"
                       placeholder="Min. 8 characters" required>
                <button type="button" class="hb-input-toggle" @click="togglePassword" tabindex="-1">👁</button>
            </div>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="hb-form-label">Confirm new password</label>
            <div class="hb-input-icon-wrap">
                <svg class="hb-input-icon" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="hb-form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="Repeat new password" required>
            </div>
        </div>

        <button type="submit" class="hb-btn-primary" :class="{ loading: loading }">
            <span x-show="!loading">Reset Password</span>
            <span x-show="loading" style="display:none;">Resetting...</span>
        </button>
    </form>
</x-guest-layout>
