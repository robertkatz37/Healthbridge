<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreStaffRequest;
use App\Models\AgencyStaff;
use App\Models\User;
use App\Services\Agency\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Agency staff invitation. Staff accounts are provisioned immediately
 * (not an "invite pending" flow) with a random password and sent a
 * standard password-reset email so they can set their own credentials —
 * reuses the existing Laravel password broker rather than building a
 * separate invitation-token system.
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $staff = $agency->staff()->with('user')->get();
        $remainingSlots = $this->planLimits->remainingStaffSlots($agency);
        $canAddStaff = $this->planLimits->canAddStaff($agency);

        return view('agency.staff', compact('agency', 'staff', 'remainingSlots', 'canAddStaff'));
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        if (!$this->planLimits->canAddStaff($agency)) {
            return back()->withErrors([
                'plan' => 'You have reached the staff account limit for your current plan. Upgrade to add more.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(24)),
        ]);
        $user->assignRole('agency_staff');

        AgencyStaff::create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'job_title' => $request->job_title,
            'is_primary_contact' => $request->boolean('is_primary_contact'),
        ]);

        Password::sendResetLink(['email' => $user->email]);

        activity()->causedBy($request->user())->performedOn($agency)
            ->withProperties(['staff_email' => $user->email])
            ->log('Staff member added');

        return back()->with('status', 'staff-added');
    }

    public function destroy(Request $request, AgencyStaff $staff): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($staff->agency_id === $agency->id, 403);

        $staff->delete();

        activity()->causedBy($request->user())->performedOn($agency)->log('Staff member removed');

        return back()->with('status', 'staff-removed');
    }
}
