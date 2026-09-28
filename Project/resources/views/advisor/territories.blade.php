<x-advisor-layout title="Territories">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Territories</li>
    @endslot

    <div class="hb-alert hb-alert-info mb-3">
        <i class="bi bi-info-circle me-2"></i>
        Leads from these cities/states will be automatically routed to you first when territory-based assignment applies.
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Your Service Areas</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTerritoryModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Territory
        </button>
    </div>

    <div class="row g-3">
        @forelse($territories as $territory)
            <div class="col-md-4">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-geo-alt-fill" style="color:var(--hb-emerald-700);"></i>
                                    <span class="fw-bold" style="color:var(--hb-gray-900);">{{ $territory->city }}, {{ $territory->state }}</span>
                                </div>
                                @if($territory->radius_miles)
                                    <div style="font-size:0.775rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $territory->radius_miles }} mile radius</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('advisor.territories.destroy', $territory) }}" onsubmit="return confirm('Remove this territory?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-geo-alt" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                        <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No territories added yet — you'll only receive leads via round robin.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="modal fade" id="addTerritoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Territory</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('advisor.territories.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">City</label>
                            <input type="text" name="city" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">State</label>
                            <input type="text" name="state" class="hb-form-control" maxlength="2" required>
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Radius (miles)</label>
                            <input type="number" name="radius_miles" class="hb-form-control" min="1" max="500">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Territory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-advisor-layout>
