@extends('layouts.admin')
@section('title', 'الرئيسية')
@section('content')
<div class="grid gap-3 sm:grid-cols-4">
    @foreach ([['الكتب', $books], ['المستخدمون', $readers->count()], ['آيات محفوظة', $memorized], ['أجهزة التذكير', $devices]] as [$label, $value])
        <div class="card px-4 py-4"><p class="text-sm text-muted">{{ $label }}</p><p class="text-3xl font-semibold">{{ $value }}</p></div>
    @endforeach
</div>

<section class="card mt-5 px-5 py-4">
    <h2 class="font-semibold">المستخدمون</h2>
    <ul class="mt-2 space-y-1">
        @forelse ($readers as $r)
            <li class="flex justify-between border-t border-line pt-1.5"><span>{{ $r->name }}</span><span class="text-muted">{{ $r->memorized_count }} آية · {{ \App\Models\ActivityDay::streak($r) }} يوم متتالي</span></li>
        @empty
            <li class="text-muted">لم يدخل أحد بعد.</li>
        @endforelse
    </ul>
</section>

<section class="card mt-5 px-5 py-4">
    <h2 class="font-semibold">نشاط آخر ٧ أيام</h2>
    <table class="mt-3 w-full text-start text-sm">
        <thead class="text-muted"><tr><th class="py-1 text-start">اليوم</th><th class="text-start">الاسم</th><th class="text-start">حفظ</th><th class="text-start">مراجعة</th><th class="text-start">أذكار</th></tr></thead>
        <tbody>
        @forelse ($week as $d)
            <tr class="border-t border-line"><td class="py-1.5">{{ \Illuminate\Support\Carbon::parse($d->date)->translatedFormat('l j F') }}</td><td>{{ $d->user?->name }}</td><td>{{ $d->ayahs_memorized }}</td><td>{{ $d->ayahs_reviewed }}</td><td>{{ $d->adhkar_sessions }}</td></tr>
        @empty
            <tr><td colspan="5" class="py-2 text-muted">لا يوجد نشاط بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card mt-5 space-y-2 px-5 py-4">
    <h2 class="font-semibold">حالة الإعداد</h2>
    <p>{{ $vapid ? '✅' : '⚠️' }} مفاتيح الإشعارات (VAPID) {{ $vapid ? 'مضبوطة' : 'غير مضبوطة — راجع README' }}</p>
    <p>{{ $telegram ? '✅' : '⚠️' }} بوت تيلغرام {{ $telegram ? 'مضبوط' : 'غير مضبوط (TELEGRAM_BOT_TOKEN)' }}</p>
    <p>📦 تخزين الكتب على القرص: <code dir="ltr">{{ $disk }}</code></p>
</section>

<div class="mt-5 flex flex-wrap gap-2">
    <a href="{{ route('admin.books.create') }}" class="btn btn-primary">رفع كتب</a>
    <a href="{{ route('admin.messages.index') }}" class="btn btn-soft">اكتب رسالة لعمتك</a>
</div>
@endsection
