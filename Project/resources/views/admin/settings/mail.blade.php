<x-admin-layout title="Mail Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">SMTP / Email</li>
    @endslot

    @if(session('status') === 'settings-updated')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Mail settings updated successfully.
        </div>
    @endif
    @if(session('status') === 'test-email-sent')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="6000">
            <i class="bi bi-check-circle me-2"></i> Test email sent successfully. Check the inbox (or storage/logs/laravel.log if using the "log" mailer).
        </div>
    @endif

    @include('admin.settings._nav')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">SMTP / Mail Configuration</h6>

                    <form method="POST" action="{{ route('admin.settings.mail.update') }}" novalidate
                          x-data="{ mailer: '{{ old('mail_mailer', $values['mail_mailer'] ?? 'log') }}' }">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="hb-form-label">Mail driver</label>
                            <select name="mail_mailer" class="hb-form-control" x-model="mailer">
                                <option value="log">Log (writes to file — safe for local dev)</option>
                                <option value="smtp">SMTP (send real emails)</option>
                                <option value="sendmail">Sendmail</option>
                            </select>
                            <small style="font-size:0.75rem;color:var(--hb-gray-600);">
                                "Log" is the safe default — emails are written to storage/logs/laravel.log instead of actually sending.
                            </small>
                        </div>

                        <div x-show="mailer === 'smtp'" x-cloak>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="hb-form-label">SMTP Host</label>
                                    <input type="text" name="mail_host" class="hb-form-control @error('mail_host') is-invalid @enderror"
                                           value="{{ old('mail_host', $values['mail_host'] ?? '') }}" placeholder="smtp.mailgun.org">
                                    @error('mail_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="hb-form-label">Port</label>
                                    <input type="number" name="mail_port" class="hb-form-control @error('mail_port') is-invalid @enderror"
                                           value="{{ old('mail_port', $values['mail_port'] ?? '587') }}">
                                    @error('mail_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="hb-form-label">Username</label>
                                    <input type="text" name="mail_username" class="hb-form-control"
                                           value="{{ old('mail_username', $values['mail_username'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="hb-form-label">Password</label>
                                    <input type="password" name="mail_password" class="hb-form-control"
                                           placeholder="{{ $hasPasswordSet ? '•••••••• (leave blank to keep current)' : 'Enter password' }}">
                                    <small style="font-size:0.75rem;color:var(--hb-gray-600);">Stored encrypted. Leave blank to keep the current password.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="hb-form-label">Encryption</label>
                                    <select name="mail_encryption" class="hb-form-control">
                                        <option value="tls" {{ old('mail_encryption', $values['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('mail_encryption', $values['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="none" {{ old('mail_encryption', $values['mail_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                            <i class="bi bi-save me-2"></i>Save Mail Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.9rem;">
                        <i class="bi bi-send me-2" style="color:var(--hb-emerald-700);"></i>Send Test Email
                    </h6>
                    <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Verify your settings work before relying on them for real emails.
                    </p>
                    <form method="POST" action="{{ route('admin.settings.mail.test') }}">
                        @csrf
                        <div class="mb-2">
                            <input type="email" name="test_email" class="hb-form-control @error('test_email') is-invalid @enderror"
                                   value="{{ old('test_email', auth()->user()->email) }}" placeholder="you@example.com" required>
                            @error('test_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100" style="border-radius:0.625rem;">
                            <i class="bi bi-send me-1"></i>Send Test Email
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
