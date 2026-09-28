<div class="text-center py-5 mb-4" style="background:var(--hb-gray-50);border-radius:1rem;border:2px solid var(--hb-emerald-100);">
    <h2 class="fw-bold mb-2" style="font-family:'Fraunces',serif;color:var(--hb-gray-900);">{{ $content['heading'] ?? '' }}</h2>
    @if(!empty($content['subheading']))
        <p style="color:var(--hb-gray-600);max-width:560px;margin:0 auto 1.25rem;">{{ $content['subheading'] }}</p>
    @endif
    @if(!empty($content['button_text']) && !empty($content['button_url']))
        <a href="{{ $content['button_url'] }}" class="hb-btn-primary d-inline-block" style="width:auto;padding:0.75rem 2rem;text-decoration:none;">{{ $content['button_text'] }}</a>
    @endif
</div>
