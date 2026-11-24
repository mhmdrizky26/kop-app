<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Masuk') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Sisi kiri: pengenal aplikasi --}}
        <div class="relative hidden overflow-hidden bg-blue-600 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-32 -left-16 size-80 rounded-full bg-white/5"></div>
            <div class="relative flex items-center gap-3">
                <x-logo terang class="size-10" />
                <span class="text-base font-semibold">{{ config('app.name') }}</span>
            </div>
            <div class="relative">
                <p class="text-xs font-semibold tracking-[0.2em] text-blue-100 uppercase">Koperasi simpan pinjam</p>
                <h1 class="mt-4 text-7xl leading-[0.9] font-black tracking-tighter xl:text-8xl">Setiap rupiah,<br>tercatat.</h1>
            </div>
            <div class="relative flex items-center justify-between gap-4 border-t border-white/15 pt-6 text-sm text-blue-100">
                <span class="flex items-center gap-2"><x-icon name="lock" class="size-4" /> Setiap anggota hanya melihat datanya sendiri</span>
                <span class="text-blue-200">© {{ now()->year }}</span>
            </div>
        </div>

        {{-- Sisi kanan: form masuk --}}
        <div class="flex items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-logo class="size-10" />
                    <span class="text-base font-semibold text-slate-900">{{ config('app.name') }}</span>
                </div>

                {{-- Pilihan jenis akun --}}
                <nav class="mb-8 grid grid-cols-2 rounded-lg bg-slate-100 p-1 text-sm font-medium" aria-label="Jenis akun">
                    <a href="{{ route('login') }}" @class([
                        'rounded-md px-3 py-2 text-center transition',
                        'bg-white text-slate-900 shadow-sm' => request()->routeIs('login'),
                        'text-slate-500 hover:text-slate-800' => ! request()->routeIs('login'),
                    ]) @if (request()->routeIs('login')) aria-current="page" @endif>Anggota</a>
                    <a href="{{ route('login.pengurus') }}" @class([
                        'rounded-md px-3 py-2 text-center transition',
                        'bg-white text-slate-900 shadow-sm' => request()->routeIs('login.pengurus'),
                        'text-slate-500 hover:text-slate-800' => ! request()->routeIs('login.pengurus'),
                    ]) @if (request()->routeIs('login.pengurus')) aria-current="page" @endif>Pengurus</a>
                </nav>

                @yield('isi')
            </div>
        </div>
    </div>
</body>
</html>
