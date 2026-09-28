<x-app-layout title="Affiliate Dashboard">
    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:52px;height:52px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-link-45deg" style="font-size:1.4rem;color:white;"></i>
                </div>
                <div>
                    <p style="font-size:0.8rem;opacity:0.8;margin-bottom:0.1rem;">Affiliate Dashboard</p>
                    <h4 class="fw-bold mb-0" style="font-family:'Fraunces',serif;">{{ auth()->user()->name }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="hb-alert hb-alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Affiliate tracking, commission dashboard, and payout history are being built in <strong>Phase 14</strong>.
    </div>
</x-app-layout>
