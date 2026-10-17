<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Services\Adhkar;

class AdhkarController extends Controller
{
    public function index(Adhkar $adhkar)
    {
        return view('adhkar.index', [
            'featured' => $adhkar->featured(),
            'others' => $adhkar->others(),
        ]);
    }

    public function show(Adhkar $adhkar, string $slug)
    {
        $category = $adhkar->find($slug) ?? abort(404);

        return view('adhkar.show', ['category' => $category]);
    }

    public function done(Adhkar $adhkar, string $slug)
    {
        abort_unless($adhkar->find($slug), 404);
        ActivityDay::bump('adhkar_sessions');

        return response()->noContent();
    }
}
