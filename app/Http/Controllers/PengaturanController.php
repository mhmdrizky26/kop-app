<?php

namespace App\Http\Controllers;

use App\Models\PengaturanAplikasi;
use App\Models\TahunBuku;
use App\Services\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PengaturanController extends Controller
{
    public function edit()
    {
        return view('pengaturan.edit', [
            'identitas' => PengaturanAplikasi::nilaiSekarang(),
            'tahun' => $this->tahun(),
            'daftarTahun' => TahunBuku::orderByDesc('mulai')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $aturan = [
            'nama' => ['required', 'string', 'max:50'],
            'simpanan_pokok' => ['required', 'integer', 'min:0'],
            'simpanan_wajib' => ['required', 'integer', 'min:0'],
            'jasa_persen' => ['required', 'numeric', 'between:0,100'],
            'jasa_metode' => ['required', Rule::in(['tetap', 'menurun'])],
            'provisi_persen' => ['required', 'numeric', 'between:0,100'],
        ];
        foreach (array_keys(TahunBuku::POS_SHU) as $kolom) {
            $aturan[$kolom] = ['required', 'numeric', 'between:0,100'];
        }
        foreach (array_keys(TahunBuku::SALDO_AWAL_NERACA) as $kolom) {
            $aturan[$kolom] = ['nullable', 'integer', 'min:0'];
        }
        foreach (array_keys(TahunBuku::PENGESAH) as $kolom) {
            $aturan[$kolom] = ['nullable', 'string', 'max:100'];
        }
        $data = $request->validate($aturan);

        $total = round(array_sum(array_intersect_key($data, TahunBuku::POS_SHU)), 2);
        if ($total != 100.0) {
            throw ValidationException::withMessages([
                'shu' => 'Total pembagian SHU harus tepat 100%. Sekarang totalnya '.persen($total).'.',
            ]);
        }

        foreach (array_keys(TahunBuku::SALDO_AWAL_NERACA) as $kolom) {
            if (array_key_exists($kolom, $data)) {
                $data[$kolom] = (int) $data[$kolom];
            }
        }

        $this->tahun()->update($data);

        return back()->with('sukses', 'Pengaturan disimpan. Pinjaman yang sudah berjalan tetap memakai tarif lamanya.');
    }

    /** Nama aplikasi, nama koperasi, alamat, dan kota; dulu diisi di file .env. Kosong berarti memakai nilai bawaan. */
    public function identitas(Request $request)
    {
        $data = $request->validate(array_map(fn () => ['nullable', 'string', 'max:150'], PengaturanAplikasi::IDENTITAS));

        foreach ($data as $kunci => $nilai) {
            PengaturanAplikasi::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }
        PengaturanAplikasi::terapkan();

        return back()->with('sukses', 'Identitas koperasi disimpan.');
    }

    /** Membuka tahun buku berikutnya dengan menyalin aturan tahun yang sedang aktif. Saldo otomatis berlanjut. */
    public function tahunBaru()
    {
        $lama = $this->tahun();
        $mulai = $lama->selesai->copy()->addDay();
        $selesai = $mulai->copy()->addYear()->subDay();

        // Saldo awal neraca tahun baru = saldo akhir neraca tahun lama.
        $neraca = Keuangan::neraca($lama);
        $saldoAwal = [
            'awal_dana_sosial' => $neraca['sumber']['Dana Sosial']['akhir'],
            'awal_cadangan' => $neraca['sumber']['Cadangan']['akhir'],
            'awal_modal_warung' => $neraca['posisi']['Modal Warung KGB']['akhir'],
            'awal_pinjaman_luar' => $neraca['posisi']['Pinjaman di Luar Anggota']['akhir'],
            'awal_paket_munggahan' => $neraca['posisi']['Pinjaman Paket Munggahan']['akhir'],
        ];

        DB::transaction(function () use ($lama, $mulai, $selesai, $saldoAwal) {
            TahunBuku::query()->update(['aktif' => false]);
            $baru = $lama->replicate(['aktif']);
            $baru->fill([
                'nama' => $mulai->year.'/'.$selesai->year,
                'mulai' => $mulai->toDateString(), 'selesai' => $selesai->toDateString(), 'aktif' => true,
            ] + array_map(fn ($nilai) => max(0, $nilai), $saldoAwal))->save();
        });

        return back()->with('sukses', 'Tahun buku baru dibuka. Saldo simpanan dan pinjaman otomatis berlanjut.');
    }
}
