<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreCareSeekerDocumentRequest;
use App\Models\CareSeeker;
use App\Models\CareSeekerDocument;
use App\Services\Family\CareSeekerFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CareSeekerDocumentController extends Controller
{
    public function __construct(
        private readonly CareSeekerFileUploadService $uploads,
    ) {}

    public function index(Request $request, CareSeeker $careSeeker): View
    {
        $this->authorize('view', $careSeeker);

        $documents = $careSeeker->documents()->latest()->get();

        return view('family.care-seekers.documents', compact('careSeeker', 'documents'));
    }

    public function store(StoreCareSeekerDocumentRequest $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->uploads->storeDocument(
            $careSeeker,
            $request->file('file'),
            $request->document_type,
            $request->user()
        );

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Document uploaded');

        return back()->with('status', 'document-added');
    }

    public function download(Request $request, CareSeeker $careSeeker, CareSeekerDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($document->care_seeker_id === $careSeeker->id, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function destroy(Request $request, CareSeeker $careSeeker, CareSeekerDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        abort_unless($document->care_seeker_id === $careSeeker->id, 403);

        $this->uploads->deleteDocument($document);

        return back()->with('status', 'document-deleted');
    }
}
