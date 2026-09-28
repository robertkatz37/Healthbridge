<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-admin-panel');

        $stats = [
            'total_users'    => User::count(),
            'total_agencies' => Agency::count(),
            'total_families' => Family::count(),
            'pending_agencies' => Agency::inModerationQueue()->count(),
        ];

        return view('dashboards.admin', compact('stats'));
    }
}
