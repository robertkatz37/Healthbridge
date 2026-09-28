<div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
    <div class="card-body p-5">
        @if(!empty($content['heading']))
            <h2 class="fw-bold mb-3" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $content['heading'] }}</h2>
        @endif
        <div style="color:var(--hb-gray-900);line-height:1.7;">{!! $content['body'] ?? '' !!}</div>
    </div>
</div>
