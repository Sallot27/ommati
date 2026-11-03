{{-- Explains a tajweed colour. Open with: $dispatch('tajweed', 'ghunnah') — or null for the full list. --}}
@php $rules = config('tajweed.rules'); @endphp
<div x-data="tajweedSheet(@js($rules))" @tajweed.window="show($event.detail)" x-show="open" x-cloak class="fixed inset-0 z-[60]" @keydown.escape.window="open = false">
    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
    <section class="safe-bottom absolute inset-x-0 bottom-0 mx-auto max-h-[85dvh] max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-3" role="dialog" aria-modal="true" aria-label="ألوان التجويد">
        <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-line"></div>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold" x-text="rule ? rules[rule].name : 'ألوان التجويد'"></h2>
            <button @click="open = false" class="grid size-10 place-items-center rounded-full hover:bg-surface-2" aria-label="إغلاق"><x-icon name="x" /></button>
        </div>

        <template x-if="rule">
            <div class="mt-3">
                <div class="flex items-center gap-3">
                    <span class="size-10 shrink-0 rounded-full" :style="'background:' + color(rule)"></span>
                    <p class="flex-1 text-[1.05rem]" x-text="rules[rule].rule"></p>
                </div>
                <div class="mt-4 rounded-2xl bg-surface-2 px-4 py-3">
                    <p class="text-sm font-semibold text-muted">كيف أعمل بها؟</p>
                    <p class="mt-1 text-[1.05rem]" x-text="rules[rule].how"></p>
                </div>
                <button @click="rule = null" class="mt-4 text-sm font-medium text-lapis">كل ألوان التجويد ←</button>
            </div>
        </template>

        <template x-if="!rule">
            <ul class="mt-3 divide-y divide-line">
                <template x-for="(r, k) in rules" :key="k">
                    <li>
                        <button @click="rule = k" class="flex w-full items-start gap-3 py-3 text-start">
                            <span class="mt-1 size-6 shrink-0 rounded-full" :style="'background:' + color(k)"></span>
                            <span class="flex-1">
                                <span class="block font-medium" x-text="r.name"></span>
                                <span class="block text-sm text-muted" x-text="r.how"></span>
                            </span>
                        </button>
                    </li>
                </template>
                <li>
                    <button @click="open = false; $dispatch('waqf', null)" class="flex w-full items-start gap-3 py-3 text-start">
                        <span class="tone-waqf mt-1 size-6 shrink-0 rounded-full" style="background: var(--tone)"></span>
                        <span class="flex-1"><span class="block font-medium">علامات الوقف</span><span class="block text-sm text-muted">بجانب كل سطر باللون الأصفر — اضغطي عليها لمعرفة طريقة العمل بها.</span></span>
                    </button>
                </li>
            </ul>
        </template>
        <p class="mt-5 text-xs leading-6 text-muted">ألوان التجويد من خط مصحف التجويد الملوّن (KFGQPC V4)، وأُعيد تلوينها بألوان هادئة. النقاط بجانب كل سطر تبيّن الأحكام الموجودة فيه.</p>
    </section>
</div>
