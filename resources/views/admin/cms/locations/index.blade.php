<x-admin-layout title="Location & Service Guides">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Location & Service Guides</li>
    @endslot

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-states">States</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-cities">Cities</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-services">Services</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-states">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <tbody>
                            @foreach($states as $state)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-3 py-2 fw-bold">{{ $state->name }}</td>
                                    <td class="px-3 py-2">
                                        @if($state->guide)
                                            <span class="hb-badge-verified">{{ ucfirst($state->guide->status) }}</span>
                                        @else
                                            <span style="font-size:0.75rem;color:var(--hb-gray-600);">No guide yet</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <a href="{{ route('admin.cms.locations.states.edit', $state) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Edit Guide</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-cities">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <tbody>
                            @foreach($cities as $city)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-3 py-2 fw-bold">{{ $city->name }}, {{ $city->state->code }}</td>
                                    <td class="px-3 py-2">
                                        @if($city->guide)
                                            <span class="hb-badge-verified">{{ ucfirst($city->guide->status) }}</span>
                                        @else
                                            <span style="font-size:0.75rem;color:var(--hb-gray-600);">No guide yet</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <a href="{{ route('admin.cms.locations.cities.edit', $city) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Edit Guide</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-services">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <tbody>
                            @foreach($services as $service)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-3 py-2 fw-bold">{{ $service->name }}</td>
                                    <td class="px-3 py-2">
                                        @if($service->guide)
                                            <span class="hb-badge-verified">{{ ucfirst($service->guide->status) }}</span>
                                        @else
                                            <span style="font-size:0.75rem;color:var(--hb-gray-600);">No guide yet</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <a href="{{ route('admin.cms.locations.services.edit', $service) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Edit Guide</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
