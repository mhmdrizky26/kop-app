@extends('layouts.app')

@section('judul', 'Pinjaman')
@section('keterangan', 'Angsuran dan jasa pinjaman aktif otomatis masuk ke tagihan bulanan.')

@section('aksi')
    <a href="{{ route('pinjaman.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Pinjaman baru</a>
@endsection

@section('isi')
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat utama label="Pinjaman beredar" :nilai="rupiah_singkat($beredar)" ikon="banknote" :catatan="rupiah($beredar)" />
        <x-stat label="Pinjaman aktif" :nilai="$jumlahAktif" ikon="users" catatan="Belum lunas" />
        <x-stat label="Tagihan pinjaman per bulan" :nilai="rupiah_singkat($angsuranBulanan)" ikon="clipboard" catatan="Angsuran pokok ditambah jasa" />
    </div>

    <div class="card mt-6">
        <div class="card-head">
            <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                @foreach (['aktif' => 'Aktif', 'lunas' => 'Lunas', 'semua' => 'Semua'] as $kunci => $label)
                    <a href="{{ route('pinjaman.index', ['status' => $kunci]) }}" @class(['tab', 'tab-on' => $status === $kunci])>{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Tanggal pinjam</th>
                        <th class="num">Pinjaman</th>
                        <th class="num">Angsuran / bulan</th>
                        <th class="num">Jasa / bulan</th>
                        <th class="num">Sisa</th>
                        <th class="w-40">Terbayar</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pinjaman as $p)
                        <tr>
                            <td>
                                <a href="{{ route('pinjaman.show', $p) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $p->anggota->nama }}</a>
                                <p class="text-xs text-slate-500">{{ $p->tenor }} kali angsuran</p>
                            </td>
                            <td class="whitespace-nowrap text-slate-500">{{ $p->tanggal->translatedFormat('j M Y') }}</td>
                            <td class="num">{{ rupiah($p->pokok, false) }}</td>
                            <td class="num">{{ angka($p->status === 'aktif' ? $p->tagihan_angsuran : 0) }}</td>
                            <td class="num">{{ angka($p->tagihan_jasa) }}</td>
                            <td class="num font-semibold text-slate-900">{{ $p->status === 'lunas' ? 'Lunas' : rupiah($p->sisa, false) }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $p->persen_lunas }}%"></div></div>
                                    <span class="w-9 text-right text-xs text-slate-500 tabular-nums">{{ $p->persen_lunas }}%</span>
                                </div>
                            </td>
                            <td class="text-right"><a href="{{ route('pinjaman.show', $p) }}" class="btn btn-sm btn-ghost">Rincian</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-slate-500">Tidak ada pinjaman pada kelompok ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
