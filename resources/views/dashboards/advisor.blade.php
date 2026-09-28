<x-app-layout title="Advisor Dashboard">
    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;box-shadow:0 4px 20px rgba(11,110,79,0.25);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:52px;height:52px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-badge" style="font-size:1.4rem;color:white;"></i>
                </div>
                <div>
                    <p style="font-size:0.8rem;opacity:0.8;margin-bottom:0.1rem;">Advisor Dashboard</p>
                    <h4 class="fw-bold mb-0" style="font-family:'Fraunces',serif;">{{ auth()->user()->name }}</h4>
                    @if($advisor?->territory)
                        <div style="font-size:0.8rem;opacity:0.7;">Territory: {{ $advisor->territory }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100 text-center" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <i class="bi bi-people" style="font-size:2rem;color:var(--hb-emerald-700);"></i>
                    <div class="fw-bold mt-2" style="font-size:1.75rem;color:var(--hb-gray-900);">—</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Assigned Families</div>
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.25rem;">Phase 13</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 text-center" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <i class="bi bi-arrow-left-right" style="font-size:2rem;color:var(--hb-info);"></i>
                    <div class="fw-bold mt-2" style="font-size:1.75rem;color:var(--hb-gray-900);">—</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Active Referrals</div>
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.25rem;">Phase 14</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 text-center" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <i class="bi bi-graph-up-arrow" style="font-size:2rem;color:var(--hb-gold-500);"></i>
                    <div class="fw-bold mt-2" style="font-size:1.75rem;color:var(--hb-gray-900);">—</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">Conversions This Month</div>
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.25rem;">Phase 14</div>
                </div>
            </div>
        </div>
    </div>

    <div class="hb-alert hb-alert-info mt-4">
        <i class="bi bi-info-circle me-2"></i>
        Full Advisor CRM — pipeline, notes, tasks, calendar — is being built in <strong>Phase 13</strong>.
    </div>
</x-app-layout>
