<div class="mb-4 text-center">
    @if(!empty($content['image_path']))
        <img src="{{ str_starts_with($content['image_path'], 'http') ? $content['image_path'] : asset('storage/' . $content['image_path']) }}"
             alt="{{ $content['alt_text'] ?? '' }}" class="img-fluid" style="border-radius:1rem;max-height:420px;object-fit:cover;">
    @endif
    @if(!empty($content['caption']))
        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-top:0.5rem;">{{ $content['caption'] }}</p>
    @endif
</div>
