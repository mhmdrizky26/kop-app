<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Sisi kiri: pengenal aplikasi --}}
        <div class="relative hidden overflow-hidden bg-blue-600 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-32 -left-16 size-80 rounded-full bg-white/5"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-white text-base font-bold text-blue-600">K</span>
                <span class="text-base font-semibold">{{ config('app.name') }}</span>
            </div>
            <div class="relative max-w-md">
                <h1 class="text-3xl leading-tight font-semibold tracking-tight">Simpanan dan pinjaman anggota, tercatat rapi di satu tempat.</h1>
                <p class="mt-4 text-blue-100">Pengurus mencatat setoran dalam beberapa klik. Anggota bisa melihat buku tabungannya sendiri kapan saja.</p>
            </div>
            <p class="relative text-sm text-blue-200">Aplikasi demo</p>
        </div>

        {{-- Sisi kanan: form masuk --}}
        <div class="flex items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-blue-600 text-base font-bold text-white">K</span>
                    <span class="text-base font-semibold text-slate-900">{{ config('app.name') }}</span>
                </div>

                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk</h2>
                <p class="mt-1.5 text-sm text-slate-500">Anggota masuk dengan nomor anggota dan PIN.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="username" class="label">Nomor anggota</label>
                        <input id="username" name="username" value="{{ old('username') }}" class="input" placeholder="Contoh: 12" autofocus required autocomplete="username">
                        <p class="hint">Pengurus mengisi nama pengguna pengurus.</p>
                        @error('username') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div x-data="{ lihat: false }">
                        <label for="password" class="label">PIN</label>
                        <div class="relative">
                            <input id="password" name="password" :type="lihat ? 'text' : 'password'" class="input pr-20" placeholder="6 angka" required autocomplete="current-password">
                            <button type="button" @click="lihat = !lihat" class="absolute inset-y-0 right-3 text-xs font-medium text-blue-600 hover:text-blue-800" x-text="lihat ? 'Sembunyikan' : 'Lihat'"></button>
                        </div>
                        @error('password') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="ingat" value="1" class="size-4 rounded border-slate-300 accent-blue-600"> Tetap masuk di perangkat ini
                    </label>
                    <button class="btn btn-primary w-full py-2.5">Masuk</button>
                </form>

                @if (app()->environment('local'))
                    <div class="mt-8 rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">Akun demo</p>
                        <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                            <dt>Pengurus</dt><dd class="font-medium text-slate-900">bendahara / password</dd>
                            <dt>Anggota</dt><dd class="font-medium text-slate-900">1 sampai 24 / 123456</dd>
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
