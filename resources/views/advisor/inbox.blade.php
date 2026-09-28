<x-advisor-layout title="Messages">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Messages</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($conversations->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No conversations yet.</p>
                </div>
            @else
                @foreach($conversations as $conversation)
                    @php $lead = $conversation->subject; @endphp
                    @if($lead)
                        <a href="{{ route('advisor.leads.conversation', $lead) }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="width:40px;height:40px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-person" style="color:var(--hb-emerald-700);"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $lead->family_name }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    @if($conversation->latestMessage)
                                        {{ $conversation->latestMessage->sender->name }}: {{ Str::limit($conversation->latestMessage->body, 60) }}
                                    @endif
                                </div>
                            </div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);flex-shrink:0;">
                                {{ $conversation->latestMessage?->created_at?->diffForHumans() }}
                            </div>
                        </a>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</x-advisor-layout>
