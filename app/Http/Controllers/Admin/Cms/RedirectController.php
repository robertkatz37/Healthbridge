<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        $redirects = Redirect::latest()->paginate(30);

        return view('admin.cms.redirects.index', compact('redirects'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate([
            'from_path' => ['required', 'string', 'max:255', 'unique:redirects,from_path'],
            'to_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'in:301,302'],
        ]);

        $redirect = Redirect::create([
            'from_path' => ltrim($request->from_path, '/'),
            'to_path' => $request->to_path,
            'status_code' => $request->status_code,
        ]);

        activity()->causedBy($request->user())->performedOn($redirect)->log('Redirect created: /' . $redirect->from_path . ' -> ' . $redirect->to_path);

        return back()->with('status', 'redirect-created');
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['is_active' => ['sometimes', 'boolean']]);

        $redirect->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'redirect-updated');
    }

    public function destroy(Request $request, Redirect $redirect): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        activity()->causedBy($request->user())->performedOn($redirect)->log('Redirect deleted: /' . $redirect->from_path);
        $redirect->delete();

        return back()->with('status', 'redirect-deleted');
    }
}
