<x-admin-layout title="Edit Guide — {{ $service->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cms.locations.index') }}" class="hb-link">Location & Service Guides</a></li>
        <li class="breadcrumb-item active">{{ $service->name }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <form method="POST" action="{{ route('admin.cms.locations.services.update', $service) }}">
        @csrf @method('PUT')
        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <label class="hb-form-label">Intro Content</label>
                <textarea name="intro_content" class="hb-form-control mb-3" rows="6" placeholder="Custom introductory content for the {{ $service->name }} service page...">{{ old('intro_content', $guide->intro_content) }}</textarea>

                <label class="hb-form-label">Meta Title</label>
                <input type="text" name="meta_title" class="hb-form-control mb-3" value="{{ old('meta_title', $guide->seoMeta?->meta_title) }}">
                <label class="hb-form-label">Meta Description</label>
                <textarea name="meta_description" class="hb-form-control mb-3" rows="2">{{ old('meta_description', $guide->seoMeta?->meta_description) }}</textarea>

                <label class="hb-form-label">Status</label>
                <select name="status" class="hb-form-control mb-3">
                    <option value="draft" {{ old('status', $guide->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $guide->status) === 'published' ? 'selected' : '' }}>Published</option>
                </select>

                <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;">Save Guide</button>
            </div>
        </div>
    </form>
</x-admin-layout>
