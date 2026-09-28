<x-agency-layout title="Media Gallery">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Media Gallery</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Media Gallery</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMediaModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Media
        </button>
    </div>

    @if($media->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-images" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No media added yet. Add photos to make your listing stand out.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($media as $item)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);overflow:hidden;">
                        <div style="aspect-ratio:4/3;position:relative;background:var(--hb-gray-100);">
                            @if($item->type->value === 'photo')
                                <img src="{{ asset('storage/' . $item->path) }}" alt="{{ $item->caption }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:0.5rem;">
                                    <i class="bi {{ $item->type->value === 'video' ? 'bi-camera-video' : 'bi-badge-vr' }}" style="font-size:1.75rem;color:var(--hb-gray-600);"></i>
                                    <a href="{{ $item->path }}" target="_blank" class="hb-link" style="font-size:0.75rem;">View {{ $item->type->label() }}</a>
                                </div>
                            @endif
                            <span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.6);color:white;font-size:0.65rem;font-weight:600;padding:0.2rem 0.5rem;border-radius:999px;">
                                {{ $item->type->label() }}
                            </span>
                        </div>
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <span style="font-size:0.75rem;color:var(--hb-gray-600);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $item->caption ?? 'Untitled' }}
                            </span>
                            <form method="POST" action="{{ route('agency.media.destroy', $item) }}" onsubmit="return confirm('Remove this media?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="modal fade" id="addMediaModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;" x-data="{ type: 'photo' }">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Media</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.media.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Media type</label>
                            <select name="type" class="hb-form-control" x-model="type">
                                <option value="photo">Photo</option>
                                <option value="video">Video (external link)</option>
                                <option value="virtual_tour">Virtual Tour (external link)</option>
                            </select>
                        </div>
                        <div class="mb-3" x-show="type === 'photo'">
                            <label class="hb-form-label">Photo file</label>
                            <input type="file" name="file" class="hb-form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="mb-3" x-show="type !== 'photo'" style="display:none;">
                            <label class="hb-form-label">Embed URL</label>
                            <input type="url" name="url" class="hb-form-control" placeholder="https://youtube.com/... or https://my.matterport.com/...">
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Caption (optional)</label>
                            <input type="text" name="caption" class="hb-form-control">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Media</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
