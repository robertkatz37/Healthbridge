<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin user management: list, view, role assignment, deactivation.
 * All actions authorized via UserPolicy — no inline role checks.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        $users = $query->paginate(20)->withQueryString();
        $roles  = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles', 'loginHistories');
        $roles = Role::orderBy('name')->get();

        return view('admin.users.show', compact('user', 'roles'));
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $this->authorize('assignRoles', $user);

        $request->validate([
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // Prevent removing super_admin from themselves
        if ($user->id === $request->user()->id
            && !in_array('super_admin', $request->roles)) {
            return back()->withErrors(['roles' => 'You cannot remove your own super_admin role.']);
        }

        $user->syncRoles($request->roles);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['roles' => $request->roles])
            ->log('User roles updated');

        return back()->with('status', 'roles-updated');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        activity()
            ->causedBy($request->user())
            ->withProperties(['deleted_user' => $user->email])
            ->log('User deleted by admin');

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'user-deleted');
    }
}
