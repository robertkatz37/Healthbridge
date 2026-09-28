<x-advisor-layout title="Message — {{ $lead->family_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.leads.show', $lead) }}" class="hb-link">{{ $lead->family_name }}</a></li>
        <li class="breadcrumb-item active">Messages</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Communication History</h6>

            <div style="max-height:500px;overflow-y:auto;margin-bottom:1.5rem;">
                @forelse($conversation->messages as $message)
                    @php $isMine = $message->sender_id === auth()->id(); @endphp
                    <div class="d-flex mb-3" style="{{ $isMine ? 'justify-content:flex-end;' : '' }}">
                        <div style="max-width:70%;background:{{ $isMine ? 'var(--hb-emerald-700)' : 'var(--hb-gray-50)' }};color:{{ $isMine ? 'white' : 'var(--hb-gray-900)' }};border-radius:0.875rem;padding:0.75rem 1rem;">
                            <div style="font-size:0.875rem;">{{ $message->body }}</div>
                            <div style="font-size:0.7rem;opacity:0.7;margin-top:0.25rem;">
                                {{ $message->sender->name }} &middot; {{ $message->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                        <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No messages yet. Start the conversation below.</p>
                    </div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('advisor.leads.conversation.store', $lead) }}">
                @csrf
                <div class="input-group">
                    <textarea name="body" rows="2" class="hb-form-control" placeholder="Type a message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-2" style="border-radius:0.75rem;">
                    <i class="bi bi-send me-2"></i>Send Message
                </button>
            </form>
        </div>
    </div>
</x-advisor-layout>
