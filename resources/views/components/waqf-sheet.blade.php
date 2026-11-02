{{-- Explains a waqf (stop) mark. Open with: $dispatch('waqf', 'ۖ')  — or with null for the full list. --}}
@php $waqf = config('waqf'); @endphp
<div x-data="waqfSheet(@js($waqf))" @waqf.window="show($event.detail)" x-show="open" x-cloak class="fixed inset-0 z-[60]" @keydown.escape.window="close()">
    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
    <section class="safe-bottom absolute inset-x-0 bottom-0 mx-auto max-h-[85dvh] max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-3" role="dialog" aria-modal="true" aria-label="علامات الوقف">
        <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-line"></div>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold" x-text="mark ? 'علامة ' + marks[mark].name : 'علامات الوقف في المصحف'"></h2>
            <button @click="close()" class="grid size-10 place-items-center rounded-full hover:bg-surface-2" aria-label="إغلاق"><x-icon name="x" /></button>
        </div>

        <template x-if="mark">
            <div class="mt-3">
                <div class="flex items-center gap-3">
                    <span class="waqf-chip !h-12 !min-w-14 !text-xl" :class="'tone-' + marks[mark].tone" x-text="marks[mark].label"></span>
                    <p class="flex-1 text-[1.05rem]" x-text="marks[mark].rule"></p>
                </div>
                <div class="mt-4 rounded-2xl bg-surface-2 px-4 py-3">
                    <p class="text-sm font-semibold text-muted">كيف أعمل بها؟</p>
                    <p class="mt-1 text-[1.05rem]" x-text="marks[mark].how"></p>
                </div>
                <button @click="mark = null" class="mt-4 text-sm font-medium text-lapis">كل علامات الوقف ←</button>
            </div>
        </template>

        <template x-if="!mark">
            <ul class="mt-3 divide-y divide-line">
                <template x-for="(m, ch) in marks" :key="ch">
                    <li>
                        <button @click="mark = ch" class="flex w-full items-start gap-3 py-3 text-start">
                            <span class="waqf-chip mt-0.5 !h-9 !min-w-11 !text-base" :class="'tone-' + m.tone" x-text="m.label"></span>
                            <span class="flex-1">
                                <span class="block font-medium" x-text="m.name"></span>
                                <span class="block text-sm text-muted" x-text="m.how"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </template>

        <details class="mt-5 rounded-2xl border border-line px-4 py-3">
            <summary class="cursor-pointer font-medium">كيف أقف على الكلمة؟</summary>
            <ul class="mt-2 list-disc space-y-1.5 ps-5 text-[0.98rem]">
                @foreach ($waqf['stopping'] as $tip)
                    <li>{{ $tip }}</li>
                @endforeach
            </ul>
        </details>
    </section>
</div>
