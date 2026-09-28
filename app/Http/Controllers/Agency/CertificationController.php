<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreCertificationRequest;
use App\Models\AgencyCertification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificationController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $certifications = $agency->certifications()->orderByDesc('expires_at')->get();

        return view('agency.certifications', compact('agency', 'certifications'));
    }

    public function store(StoreCertificationRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store("agencies/{$agency->id}/certifications", 'local');
        }

        $agency->certifications()->create([
            ...$request->safe()->except('document'),
            'document_path' => $documentPath,
        ]);

        activity()->causedBy($request->user())->performedOn($agency)->log('Certification added');

        return back()->with('status', 'certification-added');
    }

    public function destroy(Request $request, AgencyCertification $certification): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($certification->agency_id === $agency->id, 403);

        if ($certification->document_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($certification->document_path);
        }
        $certification->delete();

        return back()->with('status', 'certification-deleted');
    }
}
