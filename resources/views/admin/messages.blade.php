@extends('layouts.admin')
@section('title', 'رسالة لعمتك')
@section('content')
<form method="POST" action="{{ route('admin.messages.store') }}" class="card max-w-xl space-y-3 px-5 py-5">
    @csrf
    <h1 class="text-xl font-semibold">رسالة تظهر لعمتك في الصفحة الرئيسية</h1>
    <textarea name="body" rows="4" required maxlength="1000" placeholder="مثال: صباح الخير يا عمتي، لا تنسيني من دعائك 🤍" class="w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">{{ old('body') }}</textarea>
    <input name="from_name" value="{{ old('from_name') }}" placeholder="اسمك (اختياري)" class="w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
    <button class="btn btn-primary">نشر الرسالة</button>
</form>

<div class="card mt-5 divide-y divide-line">
    @foreach ($messages as $m)
        <div class="flex items-start gap-3 px-4 py-3">
            <div class="flex-1">
                <p class="whitespace-pre-line">{{ $m->body }}</p>
                <p class="text-sm text-muted">{{ $m->from_name }} · {{ $m->created_at->diffForHumans() }} {{ $m->is_active ? '· ظاهرة الآن' : '' }}</p>
            </div>
            <form method="POST" action="{{ route('admin.messages.update', $m) }}">@csrf @method('PUT')<button class="text-lapis-ink">{{ $m->is_active ? 'إخفاء' : 'إظهار' }}</button></form>
            <form method="POST" action="{{ route('admin.messages.destroy', $m) }}">@csrf @method('DELETE')<button class="text-rose">حذف</button></form>
        </div>
    @endforeach
</div>
@endsection
