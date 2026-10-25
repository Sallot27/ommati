@php
    $appTitle = \App\Models\Setting::get('app_title');
    $nav = [
        ['home', 'الرئيسية', 'home', 'home'],
        ['quran.index', 'القرآن', 'book', 'quran.*|listen|ayah.*'],
        ['hifz.index', 'الحفظ', 'sprout', 'hifz.*'],
        ['adhkar.index', 'الأذكار', 'beads', 'adhkar.*|tasbih'],
        ['more', 'المزيد', 'grid', 'more|library.*|prayer|hadith.*|settings|install'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-size="2">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ $appTitle }}</title>
    <meta name="theme-color" content="#1f4569" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f1823" media="(prefers-color-scheme: dark)">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script>
        // Apply text size and theme before paint to avoid a flash.
        (function () {
            try {
                var s = JSON.parse(localStorage.getItem('textSize') || '2');
                var t = JSON.parse(localStorage.getItem('theme') || '"auto"');
                document.documentElement.dataset.size = s;
                if (t === 'dark' || (t === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh antialiased">
    @hasSection('header')
        @yield('header')
    @else
        <header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur-md" style="padding-top: env(safe-area-inset-top)">
            <div class="mx-auto flex h-16 max-w-2xl items-center gap-2 px-3">
                @hasSection('back')
                    <a href="@yield('back')" class="grid size-11 place-items-center rounded-full text-ink hover:bg-surface-2" aria-label="رجوع">
                        <x-icon name="back" />
                    </a>
                @else
                    <span class="w-2"></span>
                @endif
                <h1 class="min-w-0 flex-1 truncate text-lg font-semibold">@yield('title', $appTitle)</h1>
                @yield('header-actions')
            </div>
        </header>
    @endif

    <main class="mx-auto max-w-2xl px-4 pb-nav pt-4">
        @if (session('status') && ! request()->routeIs('home'))
            <div class="mb-4 rounded-xl bg-teal-soft px-4 py-3 text-teal" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    @unless (View::hasSection('immersive'))
    <nav class="fixed inset-x-3 z-40 mx-auto max-w-xl rounded-[1.6rem] border border-line bg-surface/95 shadow-xl shadow-black/10 backdrop-blur-md" style="bottom: max(env(safe-area-inset-bottom), 0.75rem)" aria-label="التنقل الرئيسي">
        <ul class="grid grid-cols-5 px-1 py-1">
            @foreach ($nav as [$route, $label, $icon, $pattern])
                @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                <li>
                    <a href="{{ route($route) }}" @class([
                        'flex flex-col items-center gap-0.5 rounded-[1.3rem] py-2 text-[0.78rem]',
                        'bg-teal-soft text-teal font-semibold' => $active,
                        'text-muted hover:text-ink' => ! $active,
                    ]) @if ($active) aria-current="page" @endif>
                        <span class="grid h-7 place-items-center">
                            <x-icon :name="$icon" :size="23" />
                        </span>
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
    @endunless
    @stack('scripts')
</body>
</html>
