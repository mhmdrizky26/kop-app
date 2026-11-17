@extends('layouts.app')

@section('judul', 'SHU & Posisi Keuangan')
@section('keterangan', 'Tahun buku '.$tahun->nama.' ('.$tahun->mulai->translatedFormat('F Y').' sampai '.$tahun->selesai->translatedFormat('F Y').'), dihitung sampai hari ini.')

@section('aksi')
    <a href="{{ route('pengaturan.edit') }}" class="btn btn-ghost"><x-icon name="settings" class="size-4" /> Ubah persentase</a>
    <button type="button" onclick="window.print()" class="btn btn-ghost">Cetak</button>
@endsection

@section('isi')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Perhitungan SHU --}}
        <div class="card lg:col-span-2">
            <div class="card-head"><h2 class="card-title">Perhitungan Sisa Hasil Usaha</h2></div>
            <div class="grid gap-x-8 gap-y-6 p-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Pendapatan</p>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        @foreach ($shu['pendapatan'] as $nama => $nilai)
                            <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ $nama }}</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($nilai) }}</dd></div>
                        @endforeach
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Jumlah pendapatan</dt><dd class="tabular-nums">{{ rupiah($shu['totalPendapatan']) }}</dd></div>
                    </dl>
                </div>
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Biaya</p>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        @forelse ($shu['biaya'] as $nama => $nilai)
                            <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ $nama }}</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($nilai) }}</dd></div>
                        @empty
                            <p class="text-slate-500">Belum ada biaya tercatat.</p>
                        @endforelse
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Jumlah biaya</dt><dd class="tabular-nums">{{ rupiah($shu['totalBiaya']) }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-b-2xl bg-blue-600 px-5 py-4 text-white">
                <div>
                    <p class="text-sm text-blue-100">SHU bersih</p>
                    <p class="text-xs text-blue-200">Pendapatan dikurangi biaya</p>
                </div>
                <p class="text-2xl font-semibold tracking-tight tabular-nums">{{ rupiah($shu['bersih']) }}</p>
            </div>
        </div>

        {{-- Posisi keuangan --}}
        <div class="card h-fit">
            <div class="card-head"><h2 class="card-title">Posisi keuangan hari ini</h2></div>
            <dl class="space-y-2.5 p-5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-600">Simpanan pokok</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($posisi['pokok']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-600">Simpanan wajib</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($posisi['wajib']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-600">Simpanan manasuka</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($posisi['manasuka']) }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Total simpanan anggota</dt><dd class="tabular-nums">{{ rupiah($posisi['simpanan']) }}</dd></div>
                <div class="flex justify-between pt-3"><dt class="text-slate-600">Dipinjam anggota</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($posisi['pinjaman']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-600">Perkiraan kas</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($kas) }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Total harta</dt><dd class="tabular-nums">{{ rupiah($posisi['pinjaman'] + $kas) }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Pembagian SHU --}}
    <div class="card mt-6">
        <div class="card-head">
            <h2 class="card-title">Pembagian SHU</h2>
            <p class="text-xs text-slate-500">Persentase diatur di halaman Pengaturan</p>
        </div>
        <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($shu['pembagian'] as $p)
                <div class="bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-slate-600">{{ $p['nama'] }}</p>
                        <span class="badge badge-blue">{{ persen($p['persen']) }}</span>
                    </div>
                    <p class="mt-2 text-lg font-semibold text-slate-900 tabular-nums">{{ rupiah($p['jumlah']) }}</p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, $p['persen']) }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Bagian tiap anggota --}}
    <div class="card mt-6">
        <div class="card-head">
            <div>
                <h2 class="card-title">Bagian SHU tiap anggota</h2>
                <p class="mt-0.5 text-xs text-slate-500">Jasa simpanan dibagi sebanding saldo simpanan. Jasa pinjaman dibagi sebanding jasa yang dibayar anggota.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="num">Saldo simpanan</th>
                        <th class="num">Jasa dibayar</th>
                        <th class="num">Dari jasa simpanan</th>
                        <th class="num">Dari jasa pinjaman</th>
                        <th class="num">Bagian SHU</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shu['perAnggota'] as $b)
                        <tr>
                            <td><a href="{{ route('anggota.show', $b['anggota']) }}" class="font-medium whitespace-nowrap text-slate-900 hover:text-blue-700"><span class="mr-2 inline-block w-5 text-xs font-normal text-slate-400 tabular-nums">{{ $b['anggota']->nomor }}</span>{{ $b['anggota']->nama }}</a></td>
                            <td class="num">{{ rupiah($b['simpanan'], false) }}</td>
                            <td class="num">{{ angka($b['jasa_dibayar']) }}</td>
                            <td class="num">{{ angka($b['dari_simpanan']) }}</td>
                            <td class="num">{{ angka($b['dari_pinjaman']) }}</td>
                            <td class="num font-semibold text-slate-900">{{ rupiah($b['total'], false) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Jumlah</td>
                        <td class="num">{{ rupiah($shu['totalSimpanan'], false) }}</td>
                        <td class="num">{{ rupiah($shu['totalJasa'], false) }}</td>
                        <td class="num">{{ rupiah(collect($shu['perAnggota'])->sum('dari_simpanan'), false) }}</td>
                        <td class="num">{{ rupiah(collect($shu['perAnggota'])->sum('dari_pinjaman'), false) }}</td>
                        <td class="num">{{ rupiah(collect($shu['perAnggota'])->sum('total'), false) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
