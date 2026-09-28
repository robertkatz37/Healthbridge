<x-public-layout title="Find Senior Care by State | HealthsBridge" seo-description="Browse senior care agencies by state across the United States." :breadcrumb-items="$breadcrumbs->items()">
    <h1 class="fw-bold mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">Find Senior Care by State</h1>
    <div class="row g-2">
        @foreach($states as $state)
            <div class="col-md-3 col-6">
                <a href="{{ url('/' . $state->slug) }}" class="text-decoration-none">
                    <div class="card h-100" style="border-radius:0.75rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-3">
                            <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $state->name }}</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $state->cities_count }} {{ Str::plural('city', $state->cities_count) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-public-layout>
