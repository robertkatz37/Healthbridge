<x-guest-layout title="Verify Email">
    <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="var(--hb-emerald-700)" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
            </svg>
        </div>
        <h2 class="hb-auth-title">Check your email</h2>
        <p class="hb-auth-subtitle">We've sent a verification link to <strong>{{ auth()->user()->email }}</strong></p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="6000">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <p style="font-size:0.875rem;color:var(--hb-gray-600);text-align:center;margin-bottom:1.5rem;">
        Click the link in the email to verify your account and unlock all features.
        If you didn't receive it, check your spam folder or request a new one.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" x-data="authForm()" @submit="submit">
        @csrf
        <button type="submit" class="hb-btn-primary mb-3" :class="{ loading: loading }">
            <span x-show="!loading">Resend Verification Email</span>
            <span x-show="loading" style="display:none;">Sending...</span>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="hb-btn-outline" style="width:auto;padding:0.5rem 1.5rem;font-size:0.875rem;">
            Sign out
        </button>
    </form>
</x-guest-layout>
