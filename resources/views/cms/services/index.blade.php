<x-public-layout title="Senior Care Services | HealthsBridge" seo-description="Explore the types of senior care services available near you." :breadcrumb-items="$breadcrumbs->items()">
    <h1 class="fw-bold mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">Senior Care Services</h1>
    <div class="row g-3">
        @foreach($services as $service)
            <div class="col-md-4">
                <a href="{{ url('/' . $service->slug) }}" class="text-decoration-none">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            @if($service->icon)
                                <i class="bi {{ $service->icon }}" style="font-size:1.5rem;color:var(--hb-emerald-700);"></i>
                            @endif
                            <h2 class="fw-bold mt-2" style="font-size:1rem;color:var(--hb-gray-900);">{{ $service->name }}</h2>
                            <p style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $service->agencies_count }} {{ Str::plural('agency', $service->agencies_count) }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-public-layout>
