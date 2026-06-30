<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService) {}

    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $results = $this->searchService->search($request->user(), $query);

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        return view('search.index', compact('query', 'results'));
    }
}
