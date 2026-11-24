<?php

namespace App\Http\Controllers;

use App\Models\KasLain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KasController extends Controller
{
    public function index()
    {
        $tahun = $this->tahun();
        $kas = KasLain::whereDate('tanggal', '>=', $tahun->mulai)->whereDate('tanggal', '<=', $tahun->selesai)
            ->latest('tanggal')->latest('id')->get();

        $masukShu = $kas->where('masuk_shu', true);

        return view('kas.index', [
            'tahun' => $tahun,
            'kas' => $kas,
            'pendapatan' => $masukShu->where('tipe', 'pendapatan')->sum('jumlah'),
            'biaya' => $masukShu->where('tipe', 'biaya')->sum('jumlah'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipe' => ['required', Rule::in(['pendapatan', 'biaya'])],
            'kategori' => ['required', Rule::in(KasLain::KATEGORI[$request->input('tipe')] ?? [])],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        KasLain::create($data);

        return back()->with('sukses', 'Catatan kas disimpan.');
    }

    public function destroy(KasLain $kas)
    {
        $kas->delete();

        return back()->with('sukses', 'Catatan kas dihapus.');
    }
}
