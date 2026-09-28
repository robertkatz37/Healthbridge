<x-agency-layout title="Pricing">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Pricing</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Pricing Tiers</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPricingModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Pricing Tier
        </button>
    </div>

    <div class="row g-3">
        @forelse($pricing as $tier)
            <div class="col-md-4">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $tier->room_type }}</div>
                                @if($tier->care_level)
                                    <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $tier->care_level }}</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('agency.pricing.destroy', $tier) }}" onsubmit="return confirm('Remove this pricing tier?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <div style="font-size:1.5rem;font-weight:700;color:var(--hb-emerald-700);">
                            ${{ number_format($tier->monthly_price, 0) }}<span style="font-size:0.8rem;font-weight:500;color:var(--hb-gray-600);">/mo</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-tags" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                        <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No pricing tiers added yet.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="modal fade" id="addPricingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Pricing Tier</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.pricing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Room type</label>
                            <input type="text" name="room_type" class="hb-form-control" placeholder="e.g. Studio, Shared Room" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Care level (optional)</label>
                            <input type="text" name="care_level" class="hb-form-control" placeholder="e.g. Basic Care, Memory Care">
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Monthly price</label>
                            <input type="number" name="monthly_price" class="hb-form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Tier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
