<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\CareSeekerDocument;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Advisor-side download for a family's care seeker documents — the
 * existing download route (Phase 9) sits under the family.* middleware
 * group, which an advisor account has no role membership in, so a
 * separate route is needed even though the same CareSeekerDocumentPolicy
 * (extended this pass to include the assigned advisor) governs both.
 */
class DocumentController extends Controller
{
    public function download(Request $request, Lead $lead, CareSeekerDocument $document): StreamedResponse
    {
        $this->authorize('view', $lead);
        $this->authorize('view', $document);
        abort_unless($lead->care_seeker_id === $document->care_seeker_id, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }
}
