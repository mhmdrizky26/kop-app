{{-- Buku tabungan satu anggota. Butuh $buku (hasil Keuangan::bukuTabungan) dan $tahun. --}}
<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Buku tabungan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Tahun buku {{ $tahun->nama }}, satu baris per bulan</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl tbl-freeze">
            <thead>
                <tr>
                    <th rowspan="2" class="align-bottom">Bulan</th>
                    <th colspan="3" class="border-l border-slate-100 text-center!">Simpanan wajib</th>
                    <th colspan="3" class="border-l border-slate-100 text-center!">Simpanan manasuka</th>
                    <th colspan="4" class="border-l border-slate-100 text-center!">Pinjaman</th>
                </tr>
                <tr>
                    <th class="num border-l border-slate-100">Setor</th><th class="num">Tarik</th><th class="num">Saldo</th>
                    <th class="num border-l border-slate-100">Setor</th><th class="num">Tarik</th><th class="num">Saldo</th>
                    <th class="num border-l border-slate-100">Pinjam</th><th class="num">Angsur</th><th class="num">Sisa</th><th class="num">Jasa</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-slate-500">Saldo awal</td>
                    <td class="num border-l border-slate-100" colspan="3">{{ rupiah($buku['awal']['wajib'], false) }}</td>
                    <td class="num border-l border-slate-100" colspan="3">{{ rupiah($buku['awal']['manasuka'], false) }}</td>
                    <td class="num border-l border-slate-100" colspan="3">{{ rupiah($buku['awal']['pinjaman'], false) }}</td>
                    <td></td>
                </tr>
                @foreach ($buku['baris'] as $b)
                    <tr @class(['text-slate-400' => ! $b['ada']])>
                        <td class="font-medium whitespace-nowrap text-slate-900">{{ $b['bulan']->translatedFormat('F Y') }}</td>
                        <td class="num border-l border-slate-100">{{ angka($b['wajib_masuk']) }}</td>
                        <td class="num">{{ angka($b['wajib_keluar']) }}</td>
                        <td class="num font-medium text-slate-900">{{ rupiah($b['wajib'], false) }}</td>
                        <td class="num border-l border-slate-100">{{ angka($b['manasuka_masuk']) }}</td>
                        <td class="num">{{ angka($b['manasuka_keluar']) }}</td>
                        <td class="num font-medium text-slate-900">{{ rupiah($b['manasuka'], false) }}</td>
                        <td class="num border-l border-slate-100">{{ angka($b['pinjaman_baru']) }}</td>
                        <td class="num">{{ angka($b['angsuran']) }}</td>
                        <td class="num font-medium text-slate-900">{{ rupiah($b['pinjaman'], false) }}</td>
                        <td class="num">{{ angka($b['jasa']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Jumlah</td>
                    <td class="num border-l border-slate-100">{{ angka($buku['jumlah']['wajib_masuk']) }}</td>
                    <td class="num">{{ angka($buku['jumlah']['wajib_keluar']) }}</td>
                    <td class="num">{{ rupiah($buku['akhir']['wajib'], false) }}</td>
                    <td class="num border-l border-slate-100">{{ angka($buku['jumlah']['manasuka_masuk']) }}</td>
                    <td class="num">{{ angka($buku['jumlah']['manasuka_keluar']) }}</td>
                    <td class="num">{{ rupiah($buku['akhir']['manasuka'], false) }}</td>
                    <td class="num border-l border-slate-100">{{ angka($buku['jumlah']['pinjaman_baru']) }}</td>
                    <td class="num">{{ angka($buku['jumlah']['angsuran']) }}</td>
                    <td class="num">{{ rupiah($buku['akhir']['pinjaman'], false) }}</td>
                    <td class="num">{{ angka($buku['jumlah']['jasa']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
