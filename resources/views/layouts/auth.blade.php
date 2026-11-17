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
            <div class="relative max-w-lg">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-blue-50 ring-1 ring-white/20">
                    <span class="size-1.5 rounded-full bg-emerald-300"></span> Koperasi simpan pinjam
                </span>
                <h1 class="mt-5 text-4xl leading-tight font-semibold tracking-tight">Simpanan dan pinjaman anggota, tercatat rapi di satu tempat.</h1>
                <p class="mt-4 text-lg leading-relaxed text-blue-100">Pengurus mencatat setoran dalam beberapa klik. Anggota bisa melihat buku tabungannya sendiri kapan saja.</p>

                <ul class="mt-10 space-y-5">
                    @foreach ([
                        ['book', 'Buku tabungan digital', 'Simpanan pokok, wajib, dan manasuka terlihat lengkap per bulan.'],
                        ['wallet', 'Pinjaman dan angsuran', 'Sisa pinjaman dan jadwal angsuran selalu jelas.'],
                        ['pie', 'SHU yang transparan', 'Pembagian sisa hasil usaha dihitung otomatis dan terbuka.'],
                    ] as [$ikon, $judul, $isi])
                        <li class="flex gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <x-icon :name="$ikon" class="size-5" />
                            </span>
                            <div>
                                <p class="font-semibold">{{ $judul }}</p>
                                <p class="mt-0.5 text-sm text-blue-100">{{ $isi }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
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
