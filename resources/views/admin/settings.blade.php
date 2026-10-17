@extends('layouts.admin')
@section('title', 'الإعدادات')
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-xl space-y-4 px-5 py-5">
    @csrf
    <h1 class="text-xl font-semibold">الإعدادات</h1>
    <label class="block">اسم التطبيق
        <input name="app_title" value="{{ old('app_title', $values['app_title']) }}" required class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
    </label>

    <fieldset class="space-y-2">
        <legend class="font-semibold">أوقات التذكير (اتركه فارغاً لإيقافه)</legend>
        @foreach (['reminder_morning' => 'أذكار الصباح', 'reminder_daily' => 'آية اليوم وحديثه', 'reminder_hifz' => 'ورد الحفظ', 'reminder_evening' => 'أذكار المساء', 'reminder_sleep' => 'أذكار النوم'] as $key => $label)
            <label class="flex items-center justify-between gap-3">{{ $label }}
                <input type="time" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" class="rounded-xl border border-line bg-surface-2 px-3 py-2" dir="ltr">
            </label>
        @endforeach
        <label class="flex items-center gap-2"><input type="checkbox" name="reminders_enabled" value="1" @checked($values['reminders_enabled'] === '1')> تفعيل التذكيرات</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="prayer_reminders" value="1" @checked($values['prayer_reminders'] === '1')> تنبيه عند دخول وقت كل صلاة</label>
        <p class="text-sm text-muted">الأجهزة المشتركة في التذكيرات: {{ $devices }}</p>
    </fieldset>
    <button class="btn btn-primary">حفظ</button>
</form>

<div class="mt-5 flex flex-wrap gap-3">
    <form method="POST" action="{{ route('admin.push.test') }}">@csrf <button class="btn btn-soft">إرسال إشعار تجريبي</button></form>
    @if ($telegram)
        <form method="POST" action="{{ route('admin.telegram.webhook') }}">@csrf <button class="btn btn-soft">ربط بوت تيلغرام بالتطبيق</button></form>
    @endif
</div>

@if ($telegram)
    <section class="card mt-5 max-w-xl px-5 py-4 text-sm">
        <h2 class="font-semibold">حالة بوت تيلغرام</h2>
        @if (isset($webhook['error']))
            <p class="text-rose">{{ $webhook['error'] }}</p>
        @else
            <p dir="ltr" class="mt-1 break-all text-start">{{ $webhook['url'] ?: '— غير مربوط —' }}</p>
            @if (! empty($webhook['last_error_message']))<p class="text-rose">آخر خطأ: {{ $webhook['last_error_message'] }}</p>@endif
        @endif
    </section>
@endif
@endsection
