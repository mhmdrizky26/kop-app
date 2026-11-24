<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\Keuangan;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $tahun = $this->tahun();
        $periode = now()->startOfMonth();

        $daftar = Keuangan::daftarTagihan($tahun, $periode);
        $belum = $daftar->where('status', '!=', 'lunas')
            ->map(fn ($b) => ['anggota' => $b['anggota'], 'total' => $b['sisa']['total'], 'status' => $b['status']]);

        return view('dashboard', [
            'tahun' => $tahun,
            'periode' => $periode,
            'ringkasan' => Keuangan::ringkasan(),
            'kas' => Keuangan::kas(),
            'jumlahAnggota' => $daftar->count(),
            'jumlahPeminjam' => $daftar->filter(fn ($b) => $b['anggota']->pinjamanAktif->isNotEmpty())->count(),
            'sudahBayar' => $daftar->where('status', 'lunas')->count(),
            'terkumpul' => $daftar->sum(fn ($b) => $b['bayar']['total'] ?? 0),
            'belum' => $belum,
            'arus' => Keuangan::arusBulanan($tahun),
            'terbaru' => Transaksi::with('anggota')->where('sumber', '!=', 'saldo_awal')->latest('tanggal')->latest('id')->limit(7)->get(),
        ]);
    }
}
