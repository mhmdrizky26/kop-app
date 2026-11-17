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
        $bayar = Keuangan::pembayaran($periode);

        $baris = Anggota::aktif()->with('pinjamanAktif')->orderBy('nomor')->get()
            ->map(fn ($a) => [
                'anggota' => $a,
                'lunas' => $bayar->has($a->id),
                'bayar' => $bayar[$a->id] ?? null,
                'tagihan' => $bayar->has($a->id) ? null : Keuangan::tagihan($a, $tahun, $periode),
            ]);

        $belum = $baris->where('lunas', false);

        return view('tagihan.index', [
            'tahun' => $tahun,
            'periode' => $periode,
            'baris' => $baris,
            'jumlahLunas' => $baris->where('lunas', true)->count(),
            'terkumpul' => $bayar->sum('total'),
            'sisaTagihan' => $belum->sum(fn ($b) => $b['tagihan']['total']),
            'belumPotongGaji' => $belum->filter(fn ($b) => $b['anggota']->potong_gaji)->count(),
        ]);
    }

    /** Mencatat pembayaran satu anggota; jumlahnya boleh diubah dari tagihan bawaan. */
    public function bayar(Request $request)
    {
        $data = $request->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'metode' => ['required', Rule::in(array_keys(Transaksi::METODE))],
            'wajib' => ['nullable', 'integer', 'min:0'],
            'manasuka' => ['nullable', 'integer', 'min:0'],
            'angsuran' => ['array'],
            'angsuran.*' => ['nullable', 'integer', 'min:0'],
            'jasa' => ['array'],
            'jasa.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $periode = $this->periode($request);
        $anggota = Anggota::findOrFail($data['anggota_id']);

        $pinjaman = [];
        foreach ($data['angsuran'] ?? [] as $id => $angsuran) {
            $pinjaman[$id] = ['angsuran' => (int) $angsuran, 'jasa' => (int) ($data['jasa'][$id] ?? 0)];
        }

        $tercatat = Keuangan::bayarTagihan($anggota, $periode, [
            'wajib' => (int) ($data['wajib'] ?? 0),
            'manasuka' => (int) ($data['manasuka'] ?? 0),
            'pinjaman' => $pinjaman,
        ], $data['metode'], null, $request->user()->id);

        return back()->with(
            $tercatat ? 'sukses' : 'galat',
            $tercatat ? "Pembayaran {$anggota->nama} sudah dicatat." : "{$anggota->nama} sudah tercatat membayar bulan ini."
        );
    }

    /** Menandai banyak anggota sekaligus dengan jumlah tagihan bawaan. */
    public function bayarSemua(Request $request)
    {
        $request->validate(['kelompok' => ['required', Rule::in(['potong_gaji', 'semua'])]]);

        $tahun = $this->tahun();
        $periode = $this->periode($request);
        $hanyaPotongGaji = $request->kelompok === 'potong_gaji';

        $jumlah = 0;
        $anggota = Anggota::aktif()->with('pinjamanAktif')
            ->when($hanyaPotongGaji, fn ($q) => $q->where('potong_gaji', true))->get();

        foreach ($anggota as $a) {
            $metode = $a->potong_gaji ? 'potong_gaji' : 'setor';
            if (Keuangan::bayarTagihan($a, $periode, Keuangan::tagihan($a, $tahun, $periode), $metode, null, $request->user()->id)) {
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

        return back()->with('sukses', 'Pembayaran dibatalkan. Tagihan kembali berstatus belum bayar.');
    }
}
