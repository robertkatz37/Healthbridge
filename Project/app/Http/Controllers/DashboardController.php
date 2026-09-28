<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role-based dashboard redirect hub.
 * Each role's actual dashboard is built in its respective phase.
 * Phase 5 provides a functional placeholder that shows the user's role and status.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', compact('user'));
    }
}
