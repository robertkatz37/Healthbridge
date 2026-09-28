<x-agency-layout title="Staff">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Staff</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Staff Accounts</h6>
            <span style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $remainingSlots }} slot(s) remaining on your plan</span>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal" style="border-radius:0.625rem;" {{ !$canAddStaff ? 'disabled' : '' }}>
            <i class="bi bi-person-plus me-1"></i> Add Staff Member
        </button>
    </div>

    @if(!$canAddStaff)
        <div class="hb-alert hb-alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You've reached the staff limit for your current plan.
            <a href="{{ route('agency.settings.index') }}" class="hb-link">Upgrade your plan</a> to add more.
        </div>
    @endif

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($staff->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No staff accounts yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Name</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Job Title</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $member)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $member->user->avatar_url }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                            <div>
                                                <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $member->user->name }}</div>
                                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $member->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $member->job_title ?? '—' }}
                                        @if($member->is_primary_contact) <span class="hb-badge-verified ms-1">Primary</span> @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($member->user->email_verified_at)
                                            <span style="color:var(--hb-success);font-size:0.8rem;"><i class="bi bi-check-circle me-1"></i>Active</span>
                                        @else
                                            <span style="color:var(--hb-warning);font-size:0.8rem;"><i class="bi bi-clock me-1"></i>Pending Setup</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('agency.staff.destroy', $member) }}" onsubmit="return confirm('Remove this staff member?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Staff Member</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.staff.store') }}">
                    @csrf
                    <div class="modal-body">
                        <p style="font-size:0.8rem;color:var(--hb-gray-600);">
                            They'll receive an email to set up their password.
                        </p>
                        <div class="mb-3">
                            <label class="hb-form-label">Full name</label>
                            <input type="text" name="name" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Email address</label>
                            <input type="email" name="email" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Job title</label>
                            <input type="text" name="job_title" class="hb-form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_primary_contact" value="1" class="form-check-input" id="primaryContact">
                            <label class="form-check-label" for="primaryContact" style="font-size:0.85rem;">Primary contact</label>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
