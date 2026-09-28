<div class="text-center py-5 mb-4" style="background:var(--hb-emerald-900);border-radius:1rem;color:white;">
    <div class="container">
        <h1 class="fw-bold mb-3" style="font-family:'Fraunces',serif;font-size:2.25rem;">{{ $content['heading'] ?? '' }}</h1>
        @if(!empty($content['subheading']))
            <p style="font-size:1.1rem;color:rgba(255,255,255,0.85);max-width:640px;margin:0 auto 1.5rem;">{{ $content['subheading'] }}</p>
        @endif
        @if(!empty($content['button_text']) && !empty($content['button_url']))
            <a href="{{ $content['button_url'] }}" class="hb-btn-primary d-inline-block" style="width:auto;padding:0.75rem 2rem;text-decoration:none;">{{ $content['button_text'] }}</a>
        @endif
    </div>
</div>
