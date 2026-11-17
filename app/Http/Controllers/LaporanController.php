<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Services\Keuangan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /** Rekap satu bulan: apa yang masuk dan keluar per anggota, dan saldonya di akhir bulan. */
    public function rekap(Request $request)
    {
        $periode = $this->periode($request);
        $akhir = $periode->copy()->endOfMonth();

        $mutasi = Keuangan::mutasi($periode, $akhir);
        $saldo = Keuangan::saldo($akhir);

        $kolom = ['wajib', 'manasuka', 'tarik', 'pinjaman_baru', 'angsuran', 'jasa', 'simpanan', 'pinjaman'];
        $jumlah = array_fill_keys($kolom, 0);

        $baris = [];
        foreach (Anggota::orderBy('nomor')->get() as $a) {
            $m = $mutasi[$a->id] ?? [];
            $s = $saldo[$a->id] ?? null;
            if ($a->status !== 'aktif' && $m === [] && (! $s || ($s['simpanan'] == 0 && $s['pinjaman'] == 0))) {
                continue;
            }
            $b = [
                'wajib' => $m['simpanan_wajib'] ?? 0,
                'manasuka' => $m['simpanan_manasuka'] ?? 0,
                'tarik' => ($m['tarik_wajib'] ?? 0) + ($m['tarik_manasuka'] ?? 0) + ($m['tarik_pokok'] ?? 0),
                'pinjaman_baru' => $m['pinjaman'] ?? 0,
                'angsuran' => $m['angsuran'] ?? 0,
                'jasa' => $m['jasa'] ?? 0,
                'simpanan' => $s['simpanan'] ?? 0,
                'pinjaman' => $s['pinjaman'] ?? 0,
            ];
            foreach ($kolom as $k) {
                $jumlah[$k] += $b[$k];
            }
            $baris[] = $b + ['anggota' => $a];
        }

        return view('laporan.rekap', ['periode' => $periode, 'baris' => $baris, 'jumlah' => $jumlah]);
    }

    public function shu()
    {
        $tahun = $this->tahun();

        return view('laporan.shu', [
            'tahun' => $tahun,
            'shu' => Keuangan::shu($tahun),
            'posisi' => Keuangan::ringkasan(),
            'kas' => Keuangan::kas(),
        ]);
    }
}
