<x-agency-layout title="Services">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Services</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Your Services</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Service
        </button>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($services->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-list-check" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No services added yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Service</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Price From</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $service->name }}</div>
                                        @if($service->description)
                                            <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $service->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $service->price_from ? '$' . number_format($service->price_from, 2) : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="hb-badge-verified">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('agency.services.destroy', $service) }}" class="d-inline"
                                              onsubmit="return confirm('Remove this service?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Service Modal --}}
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Service</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.services.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Service name</label>
                            <input type="text" name="name" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">From catalog (optional)</label>
                            <select name="service_catalog_id" class="hb-form-control">
                                <option value="">Custom service</option>
                                @foreach($catalog as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Description</label>
                            <textarea name="description" rows="2" class="hb-form-control"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Price from</label>
                            <input type="number" name="price_from" class="hb-form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
