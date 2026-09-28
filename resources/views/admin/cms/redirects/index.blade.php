<x-admin-layout title="Redirects">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Redirects</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Add Redirect</h6>
                    <form method="POST" action="{{ route('admin.cms.redirects.store') }}">
                        @csrf
                        <label class="hb-form-label">From Path</label>
                        <input type="text" name="from_path" class="hb-form-control mb-2" placeholder="old-page" required>
                        <label class="hb-form-label">To Path</label>
                        <input type="text" name="to_path" class="hb-form-control mb-2" placeholder="new-page or https://..." required>
                        <label class="hb-form-label">Status Code</label>
                        <select name="status_code" class="hb-form-control mb-3">
                            <option value="301">301 (Permanent)</option>
                            <option value="302">302 (Temporary)</option>
                        </select>
                        <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Add Redirect</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-0">
                    @if($redirects->isEmpty())
                        <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No redirects configured.</p></div>
                    @else
                        <table class="table mb-0" style="font-size:0.875rem;">
                            <thead style="background:var(--hb-gray-50);">
                                <tr>
                                    <th class="px-3 py-2">From</th>
                                    <th class="px-3 py-2">To</th>
                                    <th class="px-3 py-2">Code</th>
                                    <th class="px-3 py-2">Hits</th>
                                    <th class="px-3 py-2 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($redirects as $redirect)
                                    <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                        <td class="px-3 py-2">/{{ $redirect->from_path }}</td>
                                        <td class="px-3 py-2">{{ $redirect->to_path }}</td>
                                        <td class="px-3 py-2">{{ $redirect->status_code }}</td>
                                        <td class="px-3 py-2">{{ $redirect->hit_count }}</td>
                                        <td class="px-3 py-2 text-end">
                                            <form method="POST" action="{{ route('admin.cms.redirects.destroy', $redirect) }}" class="d-inline" onsubmit="return confirm('Delete this redirect?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-3 py-3">{{ $redirects->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
