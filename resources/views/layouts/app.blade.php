@php
    // Menu samping: [judul kelompok => [[nama rute, pola rute aktif, label, ikon], ...]]
    $menu = [
        'Utama' => [['dashboard', 'dashboard', 'Beranda', 'home']],
        'Kegiatan' => [
            ['tagihan.index', 'tagihan.*', 'Tagihan Bulanan', 'clipboard'],
            ['transaksi.index', 'transaksi.*', 'Setor & Tarik', 'arrows'],
            ['pinjaman.index', 'pinjaman.*', 'Pinjaman', 'banknote'],
        ],
        'Data' => [
            ['anggota.index', 'anggota.*', 'Anggota', 'users'],
            ['kas.index', 'kas.*', 'Kas Lain-lain', 'wallet'],
        ],
        'Laporan' => [
            ['laporan.rekap', 'laporan.rekap', 'Rekap Bulanan', 'chart'],
            ['laporan.shu', 'laporan.shu', 'SHU & Posisi Keuangan', 'pie'],
        ],
        'Sistem' => [['pengaturan.edit', 'pengaturan.*', 'Pengaturan', 'settings']],
    ];
    $tahunAktif = \App\Models\TahunBuku::where('aktif', true)->latest('mulai')->first();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ menu: false }" :class="menu && 'max-lg:overflow-hidden'" @keydown.escape.window="menu = false">
    {{-- Latar gelap saat menu dibuka di layar kecil --}}
    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden" @click="menu = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white -translate-x-full transition-transform duration-200 lg:translate-x-0 print:hidden"
        :class="menu && 'translate-x-0!'">
        <div class="flex h-16 items-center gap-2 pr-3">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 px-5">
                <x-logo />
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold text-slate-900">{{ config('app.name') }}</span>
                    <span class="block truncate text-xs text-slate-500">Tahun buku {{ $tahunAktif?->nama }}</span>
                </span>
            </a>
            <button type="button" class="flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden" @click="menu = false" aria-label="Tutup menu">
                <x-icon name="x" class="size-4" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4">
            @foreach ($menu as $kelompok => $daftar)
                <p class="px-3 pt-5 pb-1.5 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $kelompok }}</p>
                @foreach ($daftar as [$rute, $pola, $label, $ikon])
                    @php $aktif = request()->routeIs($pola); @endphp
                    <a href="{{ route($rute) }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition lg:py-2',
                        'bg-blue-50 text-blue-700' => $aktif,
                        'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $aktif,
                    ]) @if ($aktif) aria-current="page" @endif>
                        <x-icon :name="$ikon" @class(['size-[18px]', 'text-blue-600' => $aktif, 'text-slate-400' => ! $aktif]) />
                        {{ $label }}
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="border-t border-slate-100 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">Pengurus</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Keluar" aria-label="Keluar">
                        <x-icon name="logout" class="size-4" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="min-w-0 lg:pl-64 print:pl-0">
        <header class="z-20 border-b border-slate-200 bg-white/85 backdrop-blur lg:sticky lg:top-0">
            <div class="mx-auto flex min-h-16 max-w-400 flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6 lg:px-8">
                <button type="button" class="flex size-9 shrink-0 items-center justify-center self-start rounded-lg border border-slate-200 text-slate-600 sm:self-center lg:hidden" @click="menu = true" aria-label="Buka menu">
                    <x-icon name="menu" class="size-4" />
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg leading-snug font-semibold break-words text-slate-900 sm:truncate">@yield('judul', 'Beranda')</h1>
                    @hasSection('keterangan')
                        <p class="text-sm text-slate-500 sm:truncate">@yield('keterangan')</p>
                    @endif
                </div>
                @hasSection('aksi')
                    {{-- Di HP tombol aksi turun ke baris sendiri dan melebar penuh --}}
                    <div class="flex w-full flex-wrap items-center gap-2 *:flex-1 sm:w-auto sm:*:flex-none print:hidden">@yield('aksi')</div>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-400 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
            @include('partials.pesan')
            @yield('isi')
        </main>
    </div>
</body>
</html>
