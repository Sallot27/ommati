<?php

namespace App\Http\Controllers;

use App\Models\Hadith;
use App\Services\Daily;
use App\Services\Hadeeth;

class HadithController extends Controller
{
    public function today(Daily $daily)
    {
        $hadith = $daily->hadith();

        return view('hadith.show', ['hadith' => $hadith, 'isToday' => true]);
    }

    public function show(Hadeeth $hadeeth, int $id)
    {
        $hadith = $hadeeth->find($id) ?? abort(404);

        return view('hadith.show', ['hadith' => $hadith, 'isToday' => false]);
    }

    public function saved()
    {
        return view('hadith.saved', [
            'saved' => Hadith::query()->where('is_favorite', true)->latest('updated_at')->get(),
            'recent' => Hadith::query()->where('is_favorite', false)->latest('created_at')->limit(30)->get(),
        ]);
    }

    public function toggleSave(int $id)
    {
        $hadith = Hadith::query()->findOrFail($id);
        $hadith->update(['is_favorite' => ! $hadith->is_favorite]);

        return response()->json(['saved' => $hadith->is_favorite]);
    }
}
