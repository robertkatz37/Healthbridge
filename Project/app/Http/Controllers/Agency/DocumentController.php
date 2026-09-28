<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreDocumentRequest;
use App\Models\AgencyDocument;
use App\Services\Agency\AgencyFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages private license/insurance documents. Files are stored on the
 * 'local' disk (outside the public web root, per CODING_STANDARDS.md)
 * and are only ever served through the signed, temporary download() route
 * — never a direct public URL.
 */
class DocumentController extends Controller
{
    public function __construct(
        private readonly AgencyFileUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $documents = $agency->documents()->orderByDesc('created_at')->get();

        return view('agency.documents', compact('agency', 'documents'));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        $this->uploads->storeDocument(
            $agency,
            $request->file('file'),
            $request->document_type,
            $request->expires_at
        );

        activity()->causedBy($request->user())->performedOn($agency)->log('Document uploaded');

        return back()->with('status', 'document-added');
    }

    public function download(Request $request, AgencyDocument $document): StreamedResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);
        abort_unless($document->agency_id === $agency->id, 403);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function destroy(Request $request, AgencyDocument $document): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($document->agency_id === $agency->id, 403);

        $this->uploads->deleteDocument($document);

        return back()->with('status', 'document-deleted');
    }
}
