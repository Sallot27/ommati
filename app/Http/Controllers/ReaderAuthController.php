<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Sign-in by name only, remembered on the device. */
class ReaderAuthController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        return view('hello');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:2|max:40'], [
            'name.required' => 'اكتبي اسمك أولاً',
            'name.min' => 'الاسم قصير جداً',
        ]);

        $name = User::normaliseName($data['name']);
        if (mb_strlen($name) < 2) {
            return back()->withErrors(['name' => 'الاسم قصير جداً'])->onlyInput('name');
        }

        $user = User::readerNamed($name);
        $isNew = $user->wasRecentlyCreated;
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('status', $isNew ? "أهلاً وسهلاً يا {$user->name} 🌿" : "أهلاً بعودتك يا {$user->name}");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('hello');
    }
}
