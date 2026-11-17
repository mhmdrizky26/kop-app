<?php

namespace App\Http\Controllers;

use App\Models\TahunBuku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PengaturanController extends Controller
{
    public function edit()
    {
        return view('pengaturan.edit', [
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
        $data = $request->validate($aturan);

        $total = round(array_sum(array_intersect_key($data, TahunBuku::POS_SHU)), 2);
        if ($total != 100.0) {
            throw ValidationException::withMessages([
                'shu' => 'Total pembagian SHU harus tepat 100%. Sekarang totalnya '.persen($total).'.',
            ]);
        }

        $this->tahun()->update($data);

        return back()->with('sukses', 'Pengaturan disimpan. Pinjaman yang sudah berjalan tetap memakai tarif lamanya.');
    }

    /** Membuka tahun buku berikutnya dengan menyalin aturan tahun yang sedang aktif. Saldo otomatis berlanjut. */
    public function tahunBaru()
    {
        $lama = $this->tahun();
        $mulai = $lama->selesai->copy()->addDay();
        $selesai = $mulai->copy()->addYear()->subDay();

        DB::transaction(function () use ($lama, $mulai, $selesai) {
            TahunBuku::query()->update(['aktif' => false]);
            $baru = $lama->replicate(['aktif']);
            $baru->fill([
                'nama' => $mulai->year.'/'.$selesai->year,
                'mulai' => $mulai->toDateString(), 'selesai' => $selesai->toDateString(), 'aktif' => true,
            ])->save();
        });

        return back()->with('sukses', 'Tahun buku baru dibuka. Saldo simpanan dan pinjaman otomatis berlanjut.');
    }
}
