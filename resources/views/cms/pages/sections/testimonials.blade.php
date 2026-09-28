<div class="mb-4">
    @if(!empty($content['heading']))
        <h2 class="fw-bold text-center mb-4" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $content['heading'] }}</h2>
    @endif
    <div class="row g-3">
        @foreach(($content['items'] ?? []) as $item)
            <div class="col-md-4">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div style="color:var(--hb-gold-500);margin-bottom:0.5rem;">
                            @for($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill"></i>@endfor
                        </div>
                        <p style="font-size:0.9rem;color:var(--hb-gray-900);font-style:italic;">"{{ $item['quote'] ?? '' }}"</p>
                        <div style="font-size:0.8rem;font-weight:600;color:var(--hb-gray-600);">{{ $item['author'] ?? '' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
