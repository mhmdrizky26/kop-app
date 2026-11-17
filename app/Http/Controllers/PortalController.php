<?php

namespace App\Http\Controllers;

use App\Services\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Halaman anggota. Data selalu diambil dari akun yang sedang login, tidak pernah dari alamat URL. */
class PortalController extends Controller
{
    public function index(Request $request)
    {
        $anggota = $request->user()->anggota()->with('pinjamanAktif')->firstOrFail();
        $tahun = $this->tahun();
        $periode = now()->startOfMonth();
        $bayar = Keuangan::pembayaran($periode)[$anggota->id] ?? null;

        return view('portal.index', [
            'anggota' => $anggota,
            'tahun' => $tahun,
            'periode' => $periode,
            'buku' => Keuangan::bukuTabungan($anggota, $tahun),
            'pinjaman' => $anggota->pinjaman()->denganTerbayar()->latest('tanggal')->get(),
            'transaksi' => $anggota->transaksi()->latest('tanggal')->latest('id')->limit(8)->get(),
            'bayar' => $bayar,
            'tagihan' => $bayar ? null : Keuangan::tagihan($anggota, $tahun, $periode),
            'shu' => Keuangan::shu($tahun)['perAnggota'][$anggota->id] ?? null,
        ]);
    }

    public function pin()
    {
        return view('portal.pin');
    }

    public function ubahPin(Request $request)
    {
        $data = $request->validate([
            'pin_lama' => ['required', 'string'],
            'pin_baru' => ['required', 'digits:6', 'confirmed', 'different:pin_lama'],
        ]);

        if (! Hash::check($data['pin_lama'], $request->user()->password)) {
            throw ValidationException::withMessages(['pin_lama' => 'PIN lama tidak cocok.']);
        }

        $request->user()->update(['password' => $data['pin_baru']]);

        return redirect()->route('portal')->with('sukses', 'PIN berhasil diganti.');
    }
}
