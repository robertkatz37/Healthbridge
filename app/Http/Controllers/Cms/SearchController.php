<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\Cms\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $search,
    ) {}

    public function index(Request $request): View
    {
        $query = $request->input('q', '');
        $results = $query !== '' ? $this->search->search($query) : collect();

        return view('cms.search', compact('query', 'results'));
    }
}
