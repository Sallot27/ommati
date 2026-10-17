<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Services\Adhkar;
use Illuminate\Http\Request;

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

    public function done(Request $request, Adhkar $adhkar, string $slug)
    {
        abort_unless($adhkar->find($slug), 404);
        ActivityDay::bump($request->user(), 'adhkar_sessions');
        if (in_array($slug, ['morning', 'evening'], true)) {
            ActivityDay::mark($request->user(), $slug.'_done');
        }

        return response()->noContent();
    }
}
