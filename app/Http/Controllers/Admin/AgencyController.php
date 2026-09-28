<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectAgencyRequest;
use App\Http\Requests\Admin\RequestAgencyChangesRequest;
use App\Http\Requests\Admin\StoreAgencyNoteRequest;
use App\Http\Requests\Admin\SuspendAgencyRequest;
use App\Models\Agency;
use App\Models\AgencyDocument;
use App\Services\Agency\AgencyModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-side Agency Applications Queue and moderation actions (Phase 8).
 * Distinct from the owner-facing Agency\* controllers (Phase 7) — this
 * controller never authorizes via ownership, only via the agencies.moderate
 * permission through AgencyPolicy's new moderation abilities.
 */
class AgencyController extends Controller
{
    public function __construct(
        private readonly AgencyModerationService $moderation,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewApplicationQueue', Agency::class);

        $query = Agency::with(['category', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default view: the actual moderation queue (Pending Review +
            // Changes Requested), not every agency regardless of state —
            // satisfies both "Agency Applications Queue" and "Pending
            // Review queue" as one screen with a status filter rather than
            // two separate pages, per PROJECT_ROADMAP.md Phase 8.
            $query->inModerationQueue();
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $agencies = $query->paginate(15)->withQueryString();
        $pendingCount = Agency::inModerationQueue()->count();

        return view('admin.agencies.index', compact('agencies', 'pendingCount'));
    }

    public function show(Agency $agency): View
    {
        $this->authorize('view', $agency);

        $agency->load([
            'category', 'user', 'services', 'pricing', 'coverage', 'hours',
            'certifications', 'documents', 'media', 'statusHistory.changedBy',
            'adminNotes.author', 'staff.user',
        ]);

        return view('admin.agencies.show', compact('agency'));
    }

    public function approve(Request $request, Agency $agency): RedirectResponse
    {
        $this->authorize('approve', $agency);

        $this->moderation->approve($agency, $request->user());

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'agency-approved');
    }

    public function reject(RejectAgencyRequest $request, Agency $agency): RedirectResponse
    {
        $this->moderation->reject($agency, $request->user(), $request->reason);

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'agency-rejected');
    }

    public function requestChanges(RequestAgencyChangesRequest $request, Agency $agency): RedirectResponse
    {
        $this->moderation->requestChanges($agency, $request->user(), $request->notes);

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'agency-changes-requested');
    }

    public function suspend(SuspendAgencyRequest $request, Agency $agency): RedirectResponse
    {
        $this->moderation->suspend($agency, $request->user(), $request->reason);

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'agency-suspended');
    }

    public function reactivate(Request $request, Agency $agency): RedirectResponse
    {
        $this->authorize('reactivate', $agency);

        $this->moderation->reactivate($agency, $request->user());

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'agency-reactivated');
    }

    public function storeNote(StoreAgencyNoteRequest $request, Agency $agency): RedirectResponse
    {
        $this->moderation->addNote($agency, $request->user(), $request->note);

        return redirect()->route('admin.agencies.show', $agency)
            ->with('status', 'note-added');
    }

    /**
     * Admin document download — same private-disk pattern as the owner-side
     * Agency\DocumentController::download(), but authorized via
     * agencies.moderate instead of ownership.
     */
    public function downloadDocument(Request $request, Agency $agency, AgencyDocument $document): StreamedResponse
    {
        $this->authorize('view', $agency);
        abort_unless($document->agency_id === $agency->id, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }
}
