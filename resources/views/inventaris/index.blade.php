@extends('layouts.app')

@section('judul', 'Inventaris')
@section('keterangan', 'Semua barang milik koperasi. Nilainya masuk ke neraca sebagai kekayaan di luar kas.')

@section('aksi')
    @can('ubah-data')
        <a href="{{ route('inventaris.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah barang</a>
    @endcan
@endsection

@section('isi')
    @php
        // Nilai buku dihitung sampai hari ini; untuk barang yang sudah dihapuskan, sampai tanggal penghapusan.
        $hariIni = today();
        $penyusutan = fn ($b) => $b->akumulasiPenyusutan($hariIni);
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat utama label="Nilai buku inventaris" :nilai="rupiah_singkat($totalNilaiBuku)" ikon="box" catatan="Harga perolehan dikurangi penyusutan" />
        <x-stat label="Jenis barang" :nilai="$jumlahJenis" ikon="clipboard" />
        <x-stat label="Jumlah unit" :nilai="$jumlahUnit" ikon="check" />
        <x-stat label="Perlu perbaikan" :nilai="$perluPerbaikan" ikon="info" catatan="Kondisi rusak ringan atau berat" />
    </div>

    <div class="card mt-6">
        <div class="card-head">
            <div class="flex w-full gap-1 rounded-xl bg-slate-100 p-1 *:flex-1 *:text-center sm:w-auto sm:*:flex-none">
                @foreach (['ada' => 'Masih ada', 'dihapus' => 'Sudah dihapuskan', 'semua' => 'Semua'] as $kunci => $label)
                    <a href="{{ route('inventaris.index', ['tampil' => $kunci]) }}" @class(['tab', 'tab-on' => $tampil === $kunci])>{{ $label }}</a>
                @endforeach
            </div>
            <p class="text-sm text-slate-500">{{ $barang->count() }} barang · penyusutan garis lurus per bulan</p>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl tbl-stack">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Diperoleh</th>
                        <th>Kondisi</th>
                        <th class="num">Nilai perolehan</th>
                        <th class="num">Penyusutan</th>
                        <th class="num">Nilai buku</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barang as $b)
                        <tr @class(['text-slate-400' => $b->tanggal_dihapus])>
                            <td>
                                <span class="block font-medium text-slate-900">{{ $b->nama }}</span>
                                <span class="block text-xs text-slate-500">{{ $b->lokasi ?: 'Tanpa lokasi' }}{{ $b->keterangan ? ' · '.$b->keterangan : '' }}</span>
                                @if ($b->tanggal_dihapus) <span class="block text-xs text-rose-600">Dihapuskan {{ $b->tanggal_dihapus->translatedFormat('j M Y') }}</span> @endif
                            </td>
                            <td data-label="Jumlah" class="whitespace-nowrap">{{ $b->jumlah }} {{ $b->satuan }}</td>
                            <td data-label="Diperoleh" class="whitespace-nowrap text-slate-500">
                                {{ $b->tanggal_perolehan->translatedFormat('j M Y') }}
                                <span class="block text-xs">{{ $b->umur_tahun > 0 ? 'Umur '.$b->umur_tahun.' tahun' : 'Tidak disusutkan' }}</span>
                            </td>
                            <td data-label="Kondisi"><span @class(['badge', 'badge-green' => $b->kondisi === 'baik', 'badge-amber' => $b->kondisi === 'rusak_ringan', 'badge-rose' => $b->kondisi === 'rusak_berat'])>{{ $b->label_kondisi }}</span></td>
                            <td data-label="Nilai perolehan" class="num">{{ rupiah($b->nilai, false) }}</td>
                            <td data-label="Penyusutan" class="num text-slate-500">{{ angka($penyusutan($b)) }}</td>
                            <td data-label="Nilai buku" class="num font-medium text-slate-900">{{ $b->tanggal_dihapus ? '–' : rupiah($b->nilaiBuku($hariIni), false) }}</td>
                            <td class="text-right">
                                @can('ubah-data')
                                    <a href="{{ route('inventaris.edit', $b) }}" class="btn btn-sm btn-ghost"><x-icon name="edit" class="size-3.5" /> Ubah</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-slate-500">Belum ada barang di daftar ini.</td></tr>
                    @endforelse
                </tbody>
                @if ($barang->isNotEmpty())
                    @php $masihAda = $barang->whereNull('tanggal_dihapus'); @endphp
                    <tfoot>
                        <tr>
                            <td colspan="4">Jumlah</td>
                            <td class="num" data-label="Nilai perolehan">{{ rupiah($barang->sum('nilai'), false) }}</td>
                            <td class="num" data-label="Penyusutan">{{ rupiah($barang->sum($penyusutan), false) }}</td>
                            <td class="num" data-label="Nilai buku">{{ rupiah($masihAda->sum(fn ($b) => $b->nilaiBuku($hariIni)), false) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
