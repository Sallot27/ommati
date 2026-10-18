@extends('layouts.app')
@section('title', 'ورد الختمة')
@section('back', route('home'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
@if (! $plan)
    <section class="card px-5 py-6 text-center">
        <span class="star-badge mx-auto size-16 text-2xl"><span><x-icon name="book" :size="26" /></span></span>
        <h2 class="mt-3 text-2xl font-semibold">اختمي القرآن بورد يومي</h2>
        <p class="mt-2 text-muted">اختاري المدة، ونقسم المصحف (٦٠٤ صفحات) على أيامها، ونذكّرك بوردك كل يوم.</p>
    </section>
    <form method="POST" action="{{ route('khatma.start') }}" class="mt-4 grid grid-cols-2 gap-3">
        @csrf
        @foreach ($plans as $days => $label)
            <button name="days" value="{{ $days }}" class="card px-4 py-4 text-start hover:border-teal hover:bg-teal-soft">
                <span class="block text-xl font-semibold">{{ $label }}</span>
                <span class="text-muted">{{ $D((int) ceil(604 / $days)) }} صفحة يومياً</span>
            </button>
        @endforeach
    </form>
@else
    @php $pct = round($plan['page'] / 604 * 100); @endphp
    <section class="card overflow-hidden">
        <div class="px-5 pb-4 pt-5">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-muted">ختمة في {{ $plans[$plan['days']] ?? $D($plan['days']).' يوم' }}</p>
                    <p class="text-3xl font-semibold">{{ $D($pct) }}٪</p>
                </div>
                @if (($plan['completed'] ?? 0) > 0)
                    <span class="chip bg-saffron-soft px-3 py-1 text-sm">ختمتِ {{ $D($plan['completed']) }} {{ $plan['completed'] === 1 ? 'مرة' : 'مرات' }}</span>
                @endif
            </div>
            <div class="mt-3 h-3 overflow-hidden rounded-full bg-surface-2"><div class="h-full rounded-full bg-teal" style="width: {{ $pct }}%"></div></div>
            <p class="mt-2 text-sm text-muted">قرأتِ {{ $D($plan['page']) }} من ٦٠٤ صفحة</p>
        </div>
        <div @class(['px-5 py-4', 'bg-teal-soft' => $readToday, 'bg-surface-2' => ! $readToday])>
            @if ($readToday)
                <p class="font-semibold text-teal">أتممتِ ورد اليوم، بارك الله فيكِ ✓</p>
                <p class="text-sm text-muted">ورد الغد: الصفحات {{ $D($portion['from']) }} إلى {{ $D($portion['to']) }}</p>
            @else
                <p class="text-muted">ورد اليوم</p>
                <p class="text-xl font-semibold">الصفحات {{ $D($portion['from']) }} إلى {{ $D($portion['to']) }}</p>
            @endif
        </div>
    </section>
    <a href="{{ route('khatma.read') }}" class="btn btn-primary mt-4 w-full text-lg"><x-icon name="book" /> {{ $readToday ? 'اقرئي ورد الغد مبكراً' : 'اقرئي ورد اليوم' }}</a>

    <details class="mt-6 text-center">
        <summary class="cursor-pointer text-muted">تغيير الخطة</summary>
        <form method="POST" action="{{ route('khatma.stop') }}" class="mt-3" onsubmit="return confirm('إيقاف الختمة الحالية والبدء من جديد؟')">
            @csrf
            <button class="text-rose underline">إيقاف هذه الختمة واختيار مدة جديدة</button>
        </form>
    </details>
@endif
@endsection
