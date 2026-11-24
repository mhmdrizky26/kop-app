@extends('layouts.app')

@section('judul', 'Beranda')
@section('keterangan', now()->translatedFormat('l, j F Y'))

@section('aksi')
    @can('ubah-data')
        <a href="{{ route('transaksi.create') }}" class="btn btn-ghost"><x-icon name="plus" class="size-4" /> Penarikan</a>
    @endcan
    <a href="{{ route('tagihan.index') }}" class="btn btn-primary"><x-icon name="clipboard" class="size-4" /> Tagihan bulan ini</a>
@endsection

@section('isi')
    @php
        $persen = $jumlahAnggota > 0 ? round($sudahBayar / $jumlahAnggota * 100) : 0;
        $tertinggi = max(1, collect($arus)->max(fn ($a) => max($a['masuk'], $a['keluar'])));
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat utama label="Total simpanan anggota" :nilai="rupiah_singkat($ringkasan['simpanan'])" ikon="book" :catatan="rupiah($ringkasan['simpanan'])" />
        <x-stat label="Pinjaman beredar" :nilai="rupiah_singkat($ringkasan['pinjaman'])" ikon="banknote" :catatan="$jumlahPeminjam.' anggota sedang meminjam'" />
        <x-stat label="Perkiraan kas" :nilai="rupiah_singkat($kas)" ikon="wallet" catatan="Uang masuk dikurangi uang keluar" />
        <x-stat label="Anggota aktif" :nilai="$jumlahAnggota" ikon="users" :catatan="'Tahun buku '.$tahun->nama" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Grafik uang masuk dan keluar --}}
        <div class="card lg:col-span-2">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Uang masuk dan keluar</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Per bulan, tahun buku {{ $tahun->nama }}</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-600"></span> Masuk</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-200"></span> Keluar</span>
                </div>
            </div>
            <div class="p-4 sm:p-5">
                <div class="flex h-36 items-end gap-1 sm:h-44 sm:gap-3">
                    @foreach ($arus as $a)
                        <div class="flex h-full flex-1 items-end justify-center gap-1" title="{{ $a['bulan']->translatedFormat('F Y') }}: masuk {{ rupiah($a['masuk']) }}, keluar {{ rupiah($a['keluar']) }}">
                            <div class="w-full max-w-4 rounded-t bg-blue-600" style="height: {{ max($a['masuk'] > 0 ? 2 : 0, $a['masuk'] / $tertinggi * 100) }}%"></div>
                            <div class="w-full max-w-4 rounded-t bg-blue-200" style="height: {{ max($a['keluar'] > 0 ? 2 : 0, $a['keluar'] / $tertinggi * 100) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex gap-1 border-t border-slate-100 pt-2 sm:gap-3">
                    @foreach ($arus as $a)
                        <span @class(['flex-1 text-center text-[10px] sm:text-[11px]', 'font-semibold text-blue-700' => $a['bulan']->isSameMonth($periode), 'text-slate-400' => ! $a['bulan']->isSameMonth($periode)])>{{ $a['bulan']->translatedFormat('M') }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kemajuan tagihan bulan ini --}}
        <div class="card flex flex-col">
            <div class="card-head">
                <h2 class="card-title">Tagihan {{ $periode->translatedFormat('F Y') }}</h2>
            </div>
            <div class="flex flex-1 flex-col p-5">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ $sudahBayar }}</span>
                    <span class="text-sm text-slate-500">dari {{ $jumlahAnggota }} anggota sudah lunas</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $persen }}%"></div>
                </div>
                <dl class="mt-5 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Sudah terkumpul</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($terkumpul) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Belum dibayar</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($belum->sum('total')) }}</dd></div>
                </dl>
                <a href="{{ route('tagihan.index') }}" class="btn btn-soft mt-auto w-full">Buka daftar tagihan <x-icon name="right" class="size-4" /></a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Belum bayar bulan ini</h2>
                <span class="badge badge-amber">{{ $belum->count() }} anggota</span>
            </div>
            @forelse ($belum->take(6) as $b)
                <a href="{{ route('anggota.show', $b['anggota']) }}" class="flex items-center gap-3 border-t border-slate-100 px-5 py-3 first:border-t-0 hover:bg-blue-50/30">
                    <span class="avatar">{{ $b['anggota']->inisial }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $b['anggota']->nama }}</p>
                        <p class="text-xs text-slate-500">{{ $b['anggota']->label_cara_bayar }}{{ $b['status'] === 'sebagian' ? ' · Baru dibayar sebagian' : '' }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-medium whitespace-nowrap text-slate-900 tabular-nums">{{ rupiah($b['total']) }}</span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Semua anggota sudah membayar bulan ini.</p>
            @endforelse
            @if ($belum->count() > 6)
                <a href="{{ route('tagihan.index') }}" class="block border-t border-slate-100 px-5 py-3 text-center text-sm font-medium text-blue-700 hover:bg-blue-50/40">Lihat {{ $belum->count() - 6 }} lainnya</a>
            @endif
        </div>

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Transaksi terbaru</h2>
                <a href="{{ route('transaksi.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">Lihat semua</a>
            </div>
            @forelse ($terbaru as $t)
                <div class="flex items-center gap-3 border-t border-slate-100 px-5 py-3 first:border-t-0">
                    <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full', 'bg-blue-50 text-blue-600' => $t->arah === 'masuk', 'bg-slate-100 text-slate-500' => $t->arah === 'keluar'])>
                        <x-icon :name="$t->arah === 'masuk' ? 'down-left' : 'up-right'" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $t->anggota->nama }}</p>
                        <p class="text-xs text-slate-500">{{ $t->label }} · {{ $t->tanggal->translatedFormat('j M') }}</p>
                    </div>
                    <span @class(['shrink-0 text-sm font-medium whitespace-nowrap tabular-nums', 'text-slate-900' => $t->arah === 'masuk', 'text-slate-500' => $t->arah === 'keluar'])>{{ $t->arah === 'keluar' ? '−' : '+' }}{{ rupiah($t->jumlah, false) }}</span>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi.</p>
            @endforelse
        </div>
    </div>
@endsection
