<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Services\Keuangan;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $tahun = $this->tahun();
        $periode = now()->startOfMonth();

        $aktif = Anggota::aktif()->with('pinjamanAktif')->orderBy('nomor')->get();
        $bayar = Keuangan::pembayaran($periode);
        $belum = $aktif->reject(fn ($a) => $bayar->has($a->id))
            ->map(fn ($a) => ['anggota' => $a, 'total' => Keuangan::tagihan($a, $tahun, $periode)['total']]);

        return view('dashboard', [
            'tahun' => $tahun,
            'periode' => $periode,
            'ringkasan' => Keuangan::ringkasan(),
            'kas' => Keuangan::kas(),
            'jumlahAnggota' => $aktif->count(),
            'jumlahPeminjam' => $aktif->filter(fn ($a) => $a->pinjamanAktif->isNotEmpty())->count(),
            'sudahBayar' => $aktif->filter(fn ($a) => $bayar->has($a->id))->count(),
            'terkumpul' => $bayar->sum('total'),
            'belum' => $belum,
            'arus' => Keuangan::arusBulanan($tahun),
            'terbaru' => Transaksi::with('anggota')->where('sumber', '!=', 'saldo_awal')->latest('tanggal')->latest('id')->limit(7)->get(),
        ]);
    }
}
