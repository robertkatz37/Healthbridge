<x-family-layout title="Saved Agencies">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Saved Agencies</li>
    @endslot

    @if($favorites->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-bookmark-heart" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-3" style="color:var(--hb-gray-600);">No saved agencies yet.</p>
                <a href="{{ route('agencies.index') }}" class="btn btn-primary" style="border-radius:0.75rem;">
                    <i class="bi bi-search me-2"></i>Browse Agencies
                </a>
            </div>
        </div>
    @else
        <form method="GET" action="{{ route('family.compare') }}" id="compareForm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">
                    {{ $favorites->count() }} Saved {{ Str::plural('Agency', $favorites->count()) }}
                </h6>
                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;" id="compareBtn" disabled>
                    <i class="bi bi-columns-gap me-1"></i>Compare Selected
                </button>
            </div>

            <div class="row g-3">
                @foreach($favorites as $favorite)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input compare-check" name="agencies[]" value="{{ $favorite->agency->id }}" id="cmp_{{ $favorite->agency->id }}">
                                    <label class="form-check-label" for="cmp_{{ $favorite->agency->id }}" style="font-size:0.8rem;color:var(--hb-gray-600);">Select to compare</label>
                                </div>
                                <div class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                                    <a href="{{ route('agencies.show', $favorite->agency) }}" class="text-decoration-none" style="color:inherit;">{{ $favorite->agency->name }}</a>
                                </div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $favorite->agency->city }}, {{ $favorite->agency->state }}
                                </div>
                                <span class="hb-badge-verified">{{ $favorite->agency->category?->name }}</span>

                                <form method="POST" action="{{ route('family.favorites.toggle', $favorite->agency) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="border-radius:0.5rem;">
                                        <i class="bi bi-bookmark-x me-1"></i>Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    @endif

    @push('scripts')
    <script>
        function updateCompareBtn() {
            const checked = document.querySelectorAll('.compare-check:checked').length;
            const btn = document.getElementById('compareBtn');
            if (btn) btn.disabled = checked < 2;
        }
        document.querySelectorAll('.compare-check').forEach(el => el.addEventListener('change', updateCompareBtn));
    </script>
    @endpush
</x-family-layout>
