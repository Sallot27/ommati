@extends('layouts.app')
@section('title', 'التلاوات')
@section('back', route('quran.index'))

@section('content')
<div x-data="{ reciter: (() => { try { return JSON.parse(localStorage.getItem('reciter')) || @js($reciter) } catch (e) { return @js($reciter) } })(), q: '',
              choose(id) { this.reciter = id; try { localStorage.setItem('reciter', JSON.stringify(id)) } catch (e) {} ; fetch('{{ route('settings.update') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ reciter: id }) }) } }">
    <h2 class="mb-2 font-semibold">اختاري القارئ</h2>
    <ul class="grid gap-2 sm:grid-cols-2">
        @foreach ($reciters as $id => $r)
            <li>
                <button @click="choose('{{ $id }}')" class="flex w-full items-center gap-3 rounded-2xl border px-4 py-3 text-start"
                        :class="reciter === '{{ $id }}' ? 'border-teal bg-teal-soft' : 'border-line bg-surface'">
                    <span class="grid size-6 place-items-center rounded-full border-2" :class="reciter === '{{ $id }}' ? 'border-teal bg-teal text-white' : 'border-line'">
                        <x-icon name="check" :size="14" x-show="reciter === '{{ $id }}'" />
                    </span>
                    <span>
                        <span class="block font-medium">{{ $r['name'] }}</span>
                        <span class="text-sm text-muted">{{ $r['note'] }}</span>
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <h2 class="mb-2 mt-6 font-semibold">اختاري السورة</h2>
    <input x-model="q" type="search" placeholder="ابحثي عن سورة" class="mb-3 w-full rounded-2xl border border-line bg-surface px-4 py-3 text-lg outline-none focus:border-lapis">
    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
        @foreach ($surahs as $s)
            <li x-show="!q || @js($s['name']).includes(q.trim())">
                <a href="{{ route('quran.show', $s['n']) }}?play=1" class="flex items-center gap-4 px-4 py-3 hover:bg-surface-2">
                    <span class="grid size-10 place-items-center rounded-full bg-teal-soft text-teal"><x-icon name="play" :size="18" /></span>
                    <span class="flex-1 text-lg">سورة {{ $s['name'] }}</span>
                    <span class="text-sm text-muted">{{ \App\Services\Quran::digits($s['count']) }} آية</span>
                </a>
            </li>
        @endforeach
    </ul>
    <p class="mt-4 text-center text-sm text-muted">التلاوات من مكتبة Islamic Network الصوتية.</p>
</div>
@endsection
