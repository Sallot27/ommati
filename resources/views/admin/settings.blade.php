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
        @foreach (['reminder_morning' => 'أذكار الصباح', 'reminder_daily' => 'آية اليوم وحديثه', 'reminder_hifz' => 'ورد الحفظ', 'reminder_evening' => 'أذكار المساء', 'reminder_sleep' => 'أذكار النوم', 'reminder_fasting' => 'ليلة أيام الصيام المستحب', 'reminder_friday' => 'صباح الجمعة'] as $key => $label)
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

<section class="card mt-5 max-w-xl px-5 py-4">
    <h2 class="text-lg font-semibold">بوت تيلغرام للكتب</h2>
    @if (! $telegram)
        <p class="mt-2 text-muted">أضف <code dir="ltr">TELEGRAM_BOT_TOKEN</code> في متغيرات البيئة على Laravel Cloud ثم انشر التطبيق.</p>
    @else
        <ol class="mt-2 list-inside list-decimal space-y-1 text-[0.95rem] leading-relaxed">
            <li>اضغط «ربط بوت تيلغرام بالتطبيق» بالأعلى (مرة واحدة).</li>
            <li>أضف البوت <strong>مشرفاً</strong> في قناتك، وانشر فيها أي كتاب — أو أرسل للبوت رسالة بالخاص.</li>
            <li>حدّث هذه الصفحة واضغط «سماح» بجانب القناة أو حسابك.</li>
            <li>الكتب الجديدة في القناة تُضاف تلقائياً وبصمت. الكتب القديمة: حدّدها في القناة وحوّلها للبوت بالخاص.</li>
        </ol>

        <p class="mt-3 text-sm" dir="ltr" style="text-align:start">
            @if (isset($webhook['error']))<span class="text-rose">{{ $webhook['error'] }}</span>
            @else الربط: {{ $webhook['url'] ?: '— غير مربوط —' }} @endif
        </p>
        @if (! empty($webhook['last_error_message']))<p class="text-sm text-rose">آخر خطأ: {{ $webhook['last_error_message'] }}</p>@endif

        @if ($pendingChats)
            <h3 class="mt-4 font-semibold">بانتظار السماح</h3>
            <ul class="mt-1 divide-y divide-line rounded-xl border border-line">
                @foreach ($pendingChats as $id => $c)
                    <li class="flex items-center gap-3 px-3 py-2">
                        <span class="flex-1">{{ $c['title'] }} <span class="text-sm text-muted">{{ $c['type'] === 'channel' ? 'قناة' : ($c['type'] === 'private' ? 'حساب' : $c['type']) }}</span></span>
                        <form method="POST" action="{{ route('admin.telegram.approve') }}">@csrf <input type="hidden" name="chat_id" value="{{ $id }}"><button class="btn btn-teal min-h-9 px-3 text-sm">سماح</button></form>
                    </li>
                @endforeach
            </ul>
        @endif

        <h3 class="mt-4 font-semibold">مسموح لها بإضافة الكتب</h3>
        @if ($approvedChats || $envChats)
            <ul class="mt-1 divide-y divide-line rounded-xl border border-line">
                @foreach ($approvedChats as $id => $c)
                    <li class="flex items-center gap-3 px-3 py-2">
                        <span class="flex-1">{{ $c['title'] }}</span>
                        <form method="POST" action="{{ route('admin.telegram.revoke') }}">@csrf <input type="hidden" name="chat_id" value="{{ $id }}"><button class="text-sm text-rose">إلغاء</button></form>
                    </li>
                @endforeach
                @foreach ($envChats as $id)
                    <li class="px-3 py-2 text-muted" dir="ltr" style="text-align:start">{{ $id }} (من متغيرات البيئة)</li>
                @endforeach
            </ul>
        @else
            <p class="mt-1 text-muted">لا شيء بعد.</p>
        @endif
    @endif
</section>
@endsection
