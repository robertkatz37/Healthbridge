<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreMediaRequest;
use App\Models\AgencyMedia;
use App\Services\Agency\AgencyFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public-facing media gallery. Photos are uploaded files (stored on the
 * 'public' disk); videos and virtual tours are stored as external embed
 * URLs (YouTube/Vimeo/Matterport etc.) rather than uploaded video files —
 * hosting large video files ourselves is out of scope for Phase 7.
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly AgencyFileUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $media = $agency->media()->orderBy('sort_order')->get();

        return view('agency.media', compact('agency', 'media'));
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        if ($request->type === 'photo') {
            $this->uploads->storeMedia($agency, $request->file('file'), 'photo', $request->caption);
        } else {
            $nextOrder = (int) $agency->media()->max('sort_order') + 1;
            $agency->media()->create([
                'type' => $request->type,
                'path' => $request->url,
                'caption' => $request->caption,
                'sort_order' => $nextOrder,
            ]);
        }

        activity()->causedBy($request->user())->performedOn($agency)->log('Media added');

        return back()->with('status', 'media-added');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->input('order') as $index => $mediaId) {
            AgencyMedia::where('id', $mediaId)
                ->where('agency_id', $agency->id)
                ->update(['sort_order' => $index]);
        }

        return back();
    }

    public function destroy(Request $request, AgencyMedia $media): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($media->agency_id === $agency->id, 403);

        if ($media->type->value === 'photo') {
            $this->uploads->deleteMedia($media);
        } else {
            $media->delete();
        }

        return back()->with('status', 'media-deleted');
    }
}
