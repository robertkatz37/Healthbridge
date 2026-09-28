<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    private const MAX_COMPARE = 4;

    public function show(Request $request): View
    {
        $request->validate([
            'agencies' => ['required', 'array', 'min:2', 'max:' . self::MAX_COMPARE],
            'agencies.*' => ['exists:agencies,id'],
        ]);

        $agencies = Agency::published()
            ->whereIn('id', $request->input('agencies'))
            ->with(['category', 'services', 'pricing', 'certifications'])
            ->get();

        return view('family.compare', compact('agencies'));
    }
}
