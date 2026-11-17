<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Buku Tabungan Saya') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-4xl items-center gap-3 px-4 sm:px-6">
            <a href="{{ route('portal') }}" class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white">K</span>
                <span class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</span>
            </a>
            <div class="flex-1"></div>
            <a href="{{ route('portal.pin') }}" class="btn btn-ghost btn-sm"><x-icon name="lock" class="size-3.5" /> Ganti PIN</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm"><x-icon name="logout" class="size-3.5" /> Keluar</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-6 sm:px-6">
        @include('partials.pesan')
        @yield('isi')
    </main>
</body>
</html>
