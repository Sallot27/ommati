@php $appTitle = \App\Models\Setting::get('app_title'); @endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-size="2">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appTitle }}</title>
    <meta name="theme-color" content="#1f4569">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script>try{var t=JSON.parse(localStorage.getItem('theme')||'"auto"');if(t==='dark'||(t==='auto'&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    <div class="khatam-band flex min-h-[46dvh] flex-col items-center justify-end px-6 pb-10 text-center text-white" style="padding-top: max(env(safe-area-inset-top), 2rem)">
        <img src="/icons/icon-192.png" alt="" class="rise size-24 rounded-[1.6rem] shadow-lg shadow-black/25 ring-2 ring-white/25">
        <h1 class="rise mt-5 text-3xl font-semibold" style="animation-delay: .08s">{{ $appTitle }}</h1>
        <p class="rise mt-2 max-w-xs text-lg text-white/80" style="animation-delay: .16s">أذكار وحفظ، وآية وحديث كل يوم</p>
    </div>

    <main class="mx-auto -mt-6 max-w-md px-5 pb-10">
        <form method="POST" action="{{ route('hello') }}" class="card px-5 py-6 shadow-xl shadow-black/5">
            @csrf
            <label for="name" class="block text-2xl font-semibold">ما اسمك؟</label>
            <p class="mt-1 text-muted">اكتبي اسمك فقط، ونحفظ تقدّمك وإعداداتك باسمك.</p>
            <input id="name" name="name" value="{{ old('name') }}" required minlength="2" maxlength="40" autocomplete="name" autofocus
                   placeholder="مثال: أم محمد"
                   class="mt-4 w-full rounded-2xl border-2 border-line bg-surface-2 px-4 py-4 text-xl outline-none focus:border-lapis">
            @error('name')<p class="mt-2 text-rose">{{ $message }}</p>@enderror
            <button class="btn btn-primary mt-4 w-full text-xl">ادخلي</button>
            <p class="mt-4 text-sm leading-relaxed text-muted">إذا دخلتِ من جوال آخر، اكتبي نفس الاسم وتجدين كل شيء كما تركتِه.</p>
        </form>
    </main>
</body>
</html>
