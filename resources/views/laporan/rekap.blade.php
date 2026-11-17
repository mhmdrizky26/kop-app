@extends('layouts.app')

@section('judul', 'Rekap Bulanan')
@section('keterangan', 'Uang masuk dan keluar tiap anggota pada satu bulan, dan saldonya di akhir bulan.')

@section('aksi')
    @include('partials.pilih-bulan', ['rute' => 'laporan.rekap'])
    <button type="button" onclick="window.print()" class="btn btn-ghost">Cetak</button>
@endsection

@section('isi')
    @php
        $masuk = $jumlah['wajib'] + $jumlah['manasuka'] + $jumlah['angsuran'] + $jumlah['jasa'];
        $keluar = $jumlah['tarik'] + $jumlah['pinjaman_baru'];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat utama label="Uang masuk" :nilai="rupiah_singkat($masuk)" ikon="down-left" :catatan="$periode->translatedFormat('F Y')" />
        <x-stat label="Uang keluar" :nilai="rupiah_singkat($keluar)" ikon="up-right" catatan="Penarikan dan pinjaman baru" />
        <x-stat label="Total simpanan akhir bulan" :nilai="rupiah_singkat($jumlah['simpanan'])" ikon="book" />
        <x-stat label="Pinjaman beredar akhir bulan" :nilai="rupiah_singkat($jumlah['pinjaman'])" ikon="banknote" />
    </div>

    <div class="card mt-6">
        <div class="card-head">
            <h2 class="card-title">Rekap {{ $periode->translatedFormat('F Y') }}</h2>
            <p class="text-xs text-slate-500">Semua angka dalam rupiah</p>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th rowspan="2" class="align-bottom">Anggota</th>
                        <th colspan="4" class="border-l border-slate-100 text-center!">Uang masuk</th>
                        <th colspan="2" class="border-l border-slate-100 text-center!">Uang keluar</th>
                        <th colspan="2" class="border-l border-slate-100 text-center!">Saldo akhir bulan</th>
                    </tr>
                    <tr>
                        <th class="num border-l border-slate-100">Simpanan wajib</th><th class="num">Manasuka</th><th class="num">Angsuran</th><th class="num">Jasa</th>
                        <th class="num border-l border-slate-100">Penarikan</th><th class="num">Pinjaman baru</th>
                        <th class="num border-l border-slate-100">Simpanan</th><th class="num">Sisa pinjaman</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $b)
                        <tr>
                            <td><a href="{{ route('anggota.show', $b['anggota']) }}" class="font-medium whitespace-nowrap text-slate-900 hover:text-blue-700"><span class="mr-2 inline-block w-5 text-xs font-normal text-slate-400 tabular-nums">{{ $b['anggota']->nomor }}</span>{{ $b['anggota']->nama }}</a></td>
                            <td class="num border-l border-slate-100">{{ angka($b['wajib']) }}</td>
                            <td class="num">{{ angka($b['manasuka']) }}</td>
                            <td class="num">{{ angka($b['angsuran']) }}</td>
                            <td class="num">{{ angka($b['jasa']) }}</td>
                            <td class="num border-l border-slate-100">{{ angka($b['tarik']) }}</td>
                            <td class="num">{{ angka($b['pinjaman_baru']) }}</td>
                            <td class="num border-l border-slate-100 font-medium text-slate-900">{{ rupiah($b['simpanan'], false) }}</td>
                            <td class="num">{{ angka($b['pinjaman']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-slate-500">Belum ada data pada bulan ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($baris)
                    <tfoot>
                        <tr>
                            <td>Jumlah</td>
                            <td class="num border-l border-slate-100">{{ angka($jumlah['wajib']) }}</td>
                            <td class="num">{{ angka($jumlah['manasuka']) }}</td>
                            <td class="num">{{ angka($jumlah['angsuran']) }}</td>
                            <td class="num">{{ angka($jumlah['jasa']) }}</td>
                            <td class="num border-l border-slate-100">{{ angka($jumlah['tarik']) }}</td>
                            <td class="num">{{ angka($jumlah['pinjaman_baru']) }}</td>
                            <td class="num border-l border-slate-100">{{ rupiah($jumlah['simpanan'], false) }}</td>
                            <td class="num">{{ rupiah($jumlah['pinjaman'], false) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
