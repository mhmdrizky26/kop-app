@extends('layouts.app')

@section('judul', 'Anggota')
@section('keterangan', 'Klik nama anggota untuk membuka buku tabungannya.')

@section('aksi')
    <a href="{{ route('anggota.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Anggota baru</a>
@endsection

@section('isi')
    @php
        $total = ['simpanan' => 0, 'pinjaman' => 0];
    @endphp
    <div class="card">
        <form method="GET" class="card-head">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                    @foreach (['aktif' => 'Aktif', 'keluar' => 'Keluar', 'semua' => 'Semua'] as $kunci => $label)
                        <a href="{{ route('anggota.index', ['status' => $kunci, 'cari' => request('cari')]) }}" @class(['tab', 'tab-on' => $status === $kunci])>{{ $label }}</a>
                    @endforeach
                </div>
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                    <input type="search" name="cari" value="{{ request('cari') }}" class="input w-60 pl-9" placeholder="Nama atau nomor anggota" aria-label="Cari anggota">
                </div>
            </div>
            <p class="text-sm text-slate-500">{{ $anggota->count() }} anggota</p>
        </form>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Cara bayar</th>
                        <th class="num">Simpanan wajib</th>
                        <th class="num">Simpanan manasuka</th>
                        <th class="num">Total simpanan</th>
                        <th class="num">Sisa pinjaman</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($anggota as $a)
                        @php
                            $s = $saldo[$a->id] ?? ['wajib' => 0, 'manasuka' => 0, 'simpanan' => 0, 'pinjaman' => 0];
                            $total['simpanan'] += $s['simpanan'];
                            $total['pinjaman'] += $s['pinjaman'];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('anggota.show', $a) }}" class="flex items-center gap-3">
                                    <span class="avatar">{{ $a->inisial }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $a->nama }}</span>
                                        <span class="text-xs text-slate-500">No. {{ $a->nomor }}{{ $a->jabatan ? ' · '.$a->jabatan : '' }}</span>
                                    </span>
                                    @if ($a->status === 'keluar') <span class="badge badge-slate">Keluar</span> @endif
                                </a>
                            </td>
                            <td><span @class(['badge', 'badge-blue' => $a->potong_gaji, 'badge-slate' => ! $a->potong_gaji])>{{ $a->potong_gaji ? 'Potong gaji' : 'Setor langsung' }}</span></td>
                            <td class="num">{{ rupiah($s['wajib'], false) }}</td>
                            <td class="num">{{ angka($s['manasuka']) }}</td>
                            <td class="num font-semibold text-slate-900">{{ rupiah($s['simpanan'], false) }}</td>
                            <td class="num">{{ angka($s['pinjaman']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-slate-500">Tidak ada anggota yang cocok.</td></tr>
                    @endforelse
                </tbody>
                @if ($anggota->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="4">Jumlah</td>
                            <td class="num">{{ rupiah($total['simpanan'], false) }}</td>
                            <td class="num">{{ rupiah($total['pinjaman'], false) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
