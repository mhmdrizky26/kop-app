<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Services\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagihanController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $this->tahun();
        $periode = $this->periode($request);
        $baris = Keuangan::daftarTagihan($tahun, $periode);

        return view('tagihan.index', [
            'tahun' => $tahun,
            'periode' => $periode,
            'baris' => $baris,
            'jumlahLunas' => $baris->where('status', 'lunas')->count(),
            'jumlahSebagian' => $baris->where('status', 'sebagian')->count(),
            'terkumpul' => $baris->sum(fn ($b) => $b['bayar']['total'] ?? 0),
            'sisaTagihan' => $baris->sum(fn ($b) => $b['sisa']['total']),
            'belumPotongGaji' => $baris->filter(fn ($b) => $this->adaSisaPotongGaji($b['anggota'], $b['sisa']))->count(),
        ]);
    }

    /**
     * Mencatat satu kali pembayaran. Dengan "otomatis", seluruh sisa tagihan dibayar memakai cara bayar
     * bawaan anggota; tanpa itu, jumlah tiap pos diisi pengurus (boleh sebagian).
     */
    public function bayar(Request $request)
    {
        $data = $request->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'otomatis' => ['boolean'],
            'metode' => ['required_unless:otomatis,1', 'nullable', Rule::in(array_keys(Transaksi::METODE))],
            'pokok' => ['nullable', 'integer', 'min:0'],
            'wajib' => ['nullable', 'integer', 'min:0'],
            'manasuka' => ['nullable', 'integer', 'min:0'],
            'angsuran' => ['array'],
            'angsuran.*' => ['nullable', 'integer', 'min:0'],
            'jasa' => ['array'],
            'jasa.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $tahun = $this->tahun();
        $periode = $this->periode($request);
        $anggota = Anggota::findOrFail($data['anggota_id']);
        $userId = $request->user()->id;

        if ($request->boolean('otomatis')) {
            $tercatat = Keuangan::bayarSisaTagihan($anggota, $tahun, $periode, null, null, $userId);
        } else {
            $pinjaman = [];
            foreach ($data['angsuran'] ?? [] as $id => $angsuran) {
                $pinjaman[$id] = ['angsuran' => (int) $angsuran, 'jasa' => (int) ($data['jasa'][$id] ?? 0)];
            }

            $tercatat = Keuangan::bayarTagihan($anggota, $tahun, $periode, [
                'pokok' => (int) ($data['pokok'] ?? 0),
                'wajib' => (int) ($data['wajib'] ?? 0),
                'manasuka' => (int) ($data['manasuka'] ?? 0),
                'pinjaman' => $pinjaman,
            ], $data['metode'], null, $userId);
        }

        return back()->with(
            $tercatat > 0 ? 'sukses' : 'galat',
            $tercatat > 0
                ? "Pembayaran {$anggota->nama} sudah dicatat."
                : "Tidak ada yang dicatat. Bagian yang diisi sudah lunas untuk {$anggota->nama} bulan ini."
        );
    }

    /** Membayar sisa tagihan banyak anggota sekaligus; untuk potong gaji hanya bagian yang dipotong gaji. */
    public function bayarSemua(Request $request)
    {
        $request->validate(['kelompok' => ['required', Rule::in(['potong_gaji', 'semua'])]]);

        $tahun = $this->tahun();
        $periode = $this->periode($request);
        $hanyaMetode = $request->kelompok === 'potong_gaji' ? 'potong_gaji' : null;

        $jumlah = 0;
        foreach (Keuangan::daftarTagihan($tahun, $periode) as $b) {
            if ($b['status'] === 'lunas') {
                continue;
            }
            if (Keuangan::bayarSisaTagihan($b['anggota'], $tahun, $periode, $hanyaMetode, null, $request->user()->id) > 0) {
                $jumlah++;
            }
        }

        return back()->with('sukses', $jumlah > 0
            ? "{$jumlah} anggota ditandai sudah bayar."
            : 'Tidak ada tagihan yang perlu ditandai.');
    }

    public function batal(Request $request)
    {
        $data = $request->validate(['anggota_id' => ['required', 'exists:anggota,id']]);

        Keuangan::batalTagihan((int) $data['anggota_id'], $this->periode($request));

        return back()->with('sukses', 'Semua pembayaran bulan ini dibatalkan. Tagihan kembali berstatus belum bayar.');
    }

    /** True jika anggota masih punya sisa tagihan pada bagian yang dibayar lewat potong gaji. */
    private function adaSisaPotongGaji(Anggota $anggota, array $sisa): bool
    {
        foreach (['pokok', 'wajib', 'manasuka', 'angsuran', 'jasa'] as $pos) {
            if ($sisa[$pos] > 0 && $anggota->caraBayar($pos) === 'potong_gaji') {
                return true;
            }
        }

        return false;
    }
}
