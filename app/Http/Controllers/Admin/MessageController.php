<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        return view('admin.messages', ['messages' => Message::query()->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['body' => 'required|string|max:1000', 'from_name' => 'nullable|string|max:60']);
        Message::query()->where('is_active', true)->update(['is_active' => false]);
        Message::query()->create($data + ['is_active' => true]);

        return back()->with('status', 'ستظهر الرسالة في الصفحة الرئيسية.');
    }

    public function update(Message $message)
    {
        if (! $message->is_active) {
            Message::query()->where('is_active', true)->update(['is_active' => false]);
        }
        $message->update(['is_active' => ! $message->is_active]);

        return back();
    }

    public function destroy(Message $message)
    {
        $message->delete();

        return back()->with('status', 'تم حذف الرسالة.');
    }
}
