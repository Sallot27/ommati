@extends('layouts.app')
@section('title', 'حفظ القرآن')

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
<div class="space-y-5">
    <section class="card px-5 py-5">
        <div class="flex items-end justify-between">
            <div>
                <p class="text-muted">حفظتِ حتى الآن</p>
                @if ($total > 0)
                    <p class="text-3xl font-semibold">{{ $D($total) }} <span class="text-lg font-normal text-muted">آية</span></p>
                @else
                    <p class="text-xl font-semibold">لم تبدئي بعد</p>
                @endif
            </div>
            <div class="text-end">
                <p class="text-muted">ورد اليوم</p>
                <p class="text-xl font-semibold">{{ $D(min($today, $target)) }} من {{ $D($target) }}</p>
            </div>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-2" role="progressbar" aria-valuenow="{{ $today }}" aria-valuemax="{{ $target }}">
            <div class="h-full rounded-full bg-teal" style="width: {{ min(100, $target ? $today / $target * 100 : 0) }}%"></div>
        </div>
        @if ($streak > 0)
            <p class="mt-2 text-sm text-teal">ما شاء الله، {{ $D($streak) }} {{ $streak === 1 ? 'يوم' : 'أيام' }} متتالية</p>
        @endif

        @if ($suggested)
            <a href="{{ route('hifz.learn', $suggested['n']) }}" class="btn btn-primary mt-4 w-full text-lg">
                <x-icon name="sprout" /> ابدئي ورد اليوم — سورة {{ $suggested['name'] }}
            </a>
        @endif
        @if ($dueReview > 0)
            <a href="{{ route('hifz.review') }}" class="btn btn-soft mt-2 w-full">
                <x-icon name="repeat" :size="20" /> راجعي {{ $D($dueReview) }} آية حان موعد مراجعتها
            </a>
        @endif
    </section>

    <form method="POST" action="{{ route('hifz.target') }}" class="card flex items-center gap-3 px-5 py-3">
        @csrf
        <label for="target" class="flex-1">عدد الآيات الجديدة كل يوم</label>
        <select id="target" name="target" onchange="this.form.submit()" class="rounded-xl border border-line bg-surface-2 px-3 py-2 text-lg">
            @foreach ([1, 2, 3, 5, 7, 10] as $n)
                <option value="{{ $n }}" @selected($n === $target)>{{ $D($n) }}</option>
            @endforeach
        </select>
    </form>

    @if ($inProgress->isNotEmpty())
        <section>
            <h2 class="mb-2 font-semibold">سور تحفظينها الآن</h2>
            <ul class="space-y-2">
                @foreach ($inProgress as $s)
                    <li>
                        <a href="{{ route('hifz.learn', $s['n']) }}" class="card block px-4 py-3 hover:bg-surface-2">
                            <span class="flex justify-between"><span class="font-medium">سورة {{ $s['name'] }}</span><span class="text-muted">{{ $D($s['done']) }} من {{ $D($s['count']) }}</span></span>
                            <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-surface-2"><span class="block h-full bg-teal" style="width: {{ $s['done'] / $s['count'] * 100 }}%"></span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <h2 class="font-semibold">جزء عمّ</h2>
        <p class="mb-2 text-sm text-muted">سور قصيرة، مناسبة للبداية — من الناس صعوداً</p>
        <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($juzAmma as $s)
                @php $full = $s['done'] >= $s['count']; @endphp
                <li>
                    <a href="{{ route('hifz.learn', $s['n']) }}" @class(['flex items-center justify-between rounded-xl border px-3 py-2.5', 'border-teal bg-teal-soft' => $full, 'border-line bg-surface' => ! $full])>
                        <span>{{ $s['name'] }}</span>
                        @if ($full)
                            <x-icon name="check" :size="18" class="text-teal" />
                        @elseif ($s['done'])
                            <span class="text-xs text-muted">{{ $D($s['done']) }} من {{ $D($s['count']) }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <details class="card group px-5 py-4" x-data="{ q: '' }">
        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">كل السور <x-icon name="plus" class="transition group-open:rotate-45" /></summary>
        <input x-model="q" type="search" placeholder="ابحثي عن سورة" class="my-3 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
        <ul class="divide-y divide-line">
            @foreach ($surahs as $s)
                <li x-show="!q || @js($s['name']).includes(q.trim())">
                    <a href="{{ route('hifz.learn', $s['n']) }}" class="flex justify-between py-2.5">
                        <span>{{ $D($s['n']) }}. سورة {{ $s['name'] }}</span>
                        <span class="text-sm text-muted">{{ $D($s['done']) }} من {{ $D($s['count']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </details>

    <section class="rounded-2xl bg-saffron-soft px-5 py-4">
        <h2 class="font-semibold">طريقة الحفظ</h2>
        <ol class="mt-2 list-inside list-decimal space-y-1.5 leading-relaxed">
            <li><strong>استمعي</strong> للآية بصوت القارئ عدة مرات وأنتِ تنظرين إليها.</li>
            <li><strong>اقرئيها</strong> بنفسك، والكلمات تختفي شيئاً فشيئاً.</li>
            <li><strong>سمّعيها</strong> من حفظك، وتأكدي كلمة كلمة.</li>
            <li>نربط الآيات الجديدة ببعضها، ثم <strong>نذكّرك بمراجعتها</strong> بعد يوم، ثم يومين، ثم أسبوع… حتى تثبت.</li>
        </ol>
    </section>
</div>
@endsection
