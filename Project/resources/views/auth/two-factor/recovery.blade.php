<x-app-layout title="Recovery Codes">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 12px rgba(6,61,46,0.06);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Recovery Codes</h5>
                    <p style="font-size:0.875rem;color:var(--hb-gray-600);margin-bottom:1.5rem;">
                        Store these recovery codes in a safe place. Each code can only be used once to sign in if you lose access to your authenticator app.
                    </p>

                    @if (session('status') === '2fa-enabled')
                        <div class="hb-alert hb-alert-success mb-3">
                            ✓ Two-factor authentication has been enabled. Save these recovery codes now.
                        </div>
                    @endif

                    @if (session('status') === 'recovery-codes-regenerated')
                        <div class="hb-alert hb-alert-warning mb-3">
                            ⚠ Your recovery codes have been regenerated. Your old codes are now invalid.
                        </div>
                    @endif

                    @if (session('recovery_codes'))
                        <div style="background:var(--hb-gray-50);border:1.5px solid var(--hb-gray-200);border-radius:0.875rem;padding:1.25rem;font-family:monospace;margin-bottom:1.25rem;">
                            @foreach (session('recovery_codes') as $code)
                                <div style="font-size:0.9rem;letter-spacing:0.05em;padding:0.25rem 0;color:var(--hb-gray-900);">{{ $code }}</div>
                            @endforeach
                        </div>

                        <div class="hb-alert hb-alert-warning mb-4">
                            ⚠ These codes will not be shown again. Save them now.
                        </div>
                    @else
                        <div class="hb-alert hb-alert-info mb-4">
                            Your recovery codes are stored securely and cannot be displayed again for security reasons. You can regenerate new codes below.
                        </div>
                    @endif

                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" style="border-radius:0.75rem;">
                                Regenerate New Codes
                            </button>
                        </form>
                        <a href="{{ route('two-factor.setup') }}" class="btn btn-light" style="border-radius:0.75rem;">
                            Back to 2FA Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
