<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>لوحة التحكم — @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh text-base">
    @auth
    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-x-5 gap-y-2 px-4 py-3">
            <a href="{{ route('admin.dashboard') }}" class="font-semibold">لوحة التحكم</a>
            <nav class="flex flex-wrap gap-4 text-muted">
                <a href="{{ route('admin.books.index') }}" class="hover:text-ink">الكتب</a>
                <a href="{{ route('admin.messages.index') }}" class="hover:text-ink">رسالة لعمتك</a>
                <a href="{{ route('admin.settings') }}" class="hover:text-ink">الإعدادات</a>
                <a href="{{ route('home') }}" class="hover:text-ink">التطبيق</a>
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="ms-auto">@csrf <button class="text-muted hover:text-ink">خروج</button></form>
        </div>
    </header>
    @endauth
    <main class="mx-auto max-w-4xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-teal-soft px-4 py-3 text-teal">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-saffron-soft px-4 py-3">
                @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
