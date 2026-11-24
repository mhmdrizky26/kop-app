<?php

namespace App\Http\Controllers;

use App\Services\Keuangan;
use Illuminate\Http\Request;

/** Halaman anggota. Data selalu diambil dari akun yang sedang login, tidak pernah dari alamat URL. */
class PortalController extends Controller
{
    public function index(Request $request)
    {
        $anggota = $request->user()->anggota()->with('pinjamanAktif')->firstOrFail();
        $tahun = $this->tahun();
        $periode = now()->startOfMonth();
        $bayar = Keuangan::pembayaran($periode, $anggota->id)->first();
        $tagihan = Keuangan::tagihan($anggota, $tahun, $periode);

        return view('portal.index', [
            'anggota' => $anggota,
            'tahun' => $tahun,
            'periode' => $periode,
            'buku' => Keuangan::bukuTabungan($anggota, $tahun),
            'pinjaman' => $anggota->pinjaman()->denganTerbayar()->latest('tanggal')->get(),
            'transaksi' => $anggota->transaksi()->latest('tanggal')->latest('id')->limit(8)->get(),
            'bayar' => $bayar,
            'tagihan' => $tagihan,
            'sisa' => Keuangan::sisaTagihan($tagihan, $bayar),
        ]);
    }
}
