<div class="mb-4">
    @if(!empty($content['heading']))
        <h2 class="fw-bold text-center mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $content['heading'] }}</h2>
    @endif
    <div class="row g-3">
        @foreach(($content['items'] ?? []) as $item)
            <div class="col-md-4">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4 text-center">
                        @if(!empty($item['icon']))
                            <i class="bi {{ $item['icon'] }}" style="font-size:1.75rem;color:var(--hb-emerald-700);"></i>
                        @endif
                        <h3 class="fw-bold mt-2" style="font-size:1rem;color:var(--hb-gray-900);">{{ $item['title'] ?? '' }}</h3>
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">{{ $item['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
