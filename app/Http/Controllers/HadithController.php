<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\Hadith;
use App\Services\Daily;
use App\Services\Hadeeth;
use Illuminate\Http\Request;

class HadithController extends Controller
{
    public function today(Request $request, Daily $daily)
    {
        $hadith = $daily->hadith();
        if ($hadith) {
            ActivityDay::mark($request->user(), 'hadith_read');
        }

        return view('hadith.show', [
            'hadith' => $hadith,
            'isToday' => true,
            'saved' => $hadith && $request->user()->favoriteHadiths()->whereKey($hadith->id)->exists(),
        ]);
    }

    public function show(Request $request, Hadeeth $hadeeth, int $id)
    {
        $hadith = $hadeeth->find($id) ?? abort(404);

        return view('hadith.show', [
            'hadith' => $hadith,
            'isToday' => false,
            'saved' => $request->user()->favoriteHadiths()->whereKey($hadith->id)->exists(),
        ]);
    }

    public function saved(Request $request)
    {
        $saved = $request->user()->favoriteHadiths()->orderByPivot('created_at', 'desc')->get();

        return view('hadith.saved', [
            'saved' => $saved,
            'recent' => Hadith::query()->whereNotIn('id', $saved->modelKeys())->latest('created_at')->limit(30)->get(),
        ]);
    }

    public function toggleSave(Request $request, int $id)
    {
        Hadith::query()->findOrFail($id);
        $result = $request->user()->favoriteHadiths()->toggle([$id]);

        return response()->json(['saved' => ! empty($result['attached'])]);
    }
}
