<x-agency-layout title="Notifications">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Notifications</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Notifications</h6>
        @if($notifications->whereNull('read_at')->isNotEmpty())
            <form method="POST" action="{{ route('agency.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">
                    <i class="bi bi-check2-all me-1"></i>Mark all as read
                </button>
            </form>
        @endif
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-bell" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No notifications yet.</p>
                </div>
            @else
                @foreach($notifications as $notification)
                    <div class="d-flex align-items-start gap-3 p-3" style="border-bottom:1px solid var(--hb-gray-200);{{ $notification->read_at ? '' : 'background:var(--hb-emerald-100);' }}">
                        <div style="width:36px;height:36px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                            <i class="bi bi-bell" style="font-size:0.9rem;color:var(--hb-emerald-700);"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:0.875rem;color:var(--hb-gray-900);">
                                {{ $notification->data['family_name'] ?? 'Notification' }} —
                                {{ ucfirst(str_replace('_', ' ', $notification->data['event'] ?? '')) }}
                            </div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                        @if(!$notification->read_at)
                            <form method="POST" action="{{ route('agency.notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-link p-0" style="font-size:0.75rem;">Mark read</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        @if($notifications->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-agency-layout>
