<?php

namespace App\Http\Controllers;

use App\Services\DokumenLaporan;
use App\Services\EksporLaporan;
use App\Services\Keuangan;
use Illuminate\Http\Request;

/** Laporan di layar; dengan ?format=cetak|pdf|xlsx laporan yang sama dikirim dalam bentuk formal. */
class LaporanController extends Controller
{
    /** Rekap satu bulan: apa yang masuk dan keluar per anggota, dan saldonya di akhir bulan. */
    public function rekap(Request $request)
    {
        $periode = $this->periode($request);
        if ($format = $this->format($request)) {
            return EksporLaporan::kirim(DokumenLaporan::rekap($periode, $this->tahun()), $format);
        }

        return view('laporan.rekap', ['periode' => $periode] + Keuangan::rekapBulanan($periode));
    }

    public function neraca(Request $request)
    {
        $tahun = $this->tahun();
        if ($format = $this->format($request)) {
            return EksporLaporan::kirim(DokumenLaporan::neraca($tahun), $format);
        }

        return view('laporan.neraca', ['tahun' => $tahun, 'neraca' => Keuangan::neraca($tahun)]);
    }

    public function shu(Request $request)
    {
        $tahun = $this->tahun();
        if ($format = $this->format($request)) {
            return EksporLaporan::kirim(DokumenLaporan::shu($tahun), $format);
        }

        return view('laporan.shu', [
            'tahun' => $tahun,
            'shu' => Keuangan::shu($tahun),
            'posisi' => Keuangan::ringkasan(),
            'kas' => Keuangan::kas(),
        ]);
    }

    private function format(Request $request): ?string
    {
        return in_array($request->query('format'), EksporLaporan::FORMAT, true) ? $request->query('format') : null;
    }
}
