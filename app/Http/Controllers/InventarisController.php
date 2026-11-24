<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use App\Models\KasLain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    public function index(Request $request)
    {
        $tampil = in_array($request->tampil, ['ada', 'dihapus', 'semua']) ? $request->tampil : 'ada';

        $barang = Inventaris::query()
            ->when($tampil === 'ada', fn ($q) => $q->whereNull('tanggal_dihapus'))
            ->when($tampil === 'dihapus', fn ($q) => $q->whereNotNull('tanggal_dihapus'))
            ->orderBy('nama')->get();
        $ada = Inventaris::whereNull('tanggal_dihapus')->get();

        return view('inventaris.index', [
            'barang' => $barang,
            'tampil' => $tampil,
            'jumlahJenis' => $ada->count(),
            'jumlahUnit' => $ada->sum('jumlah'),
            'totalNilaiBuku' => $ada->sum(fn (Inventaris $b) => $b->nilaiBuku(today())),
            'perluPerbaikan' => $ada->where('kondisi', '!=', 'baik')->count(),
        ]);
    }

    public function create()
    {
        return view('inventaris.form', ['barang' => new Inventaris(['tanggal_perolehan' => today(), 'jumlah' => 1, 'satuan' => 'unit', 'kondisi' => 'baik', 'umur_tahun' => 4])]);
    }

    /** Menyimpan barang baru; jika dipilih, pembeliannya sekaligus dicatat sebagai pengeluaran kas. */
    public function store(Request $request)
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request) {
            $barang = Inventaris::create($data);

            if ($request->boolean('catat_kas')) {
                KasLain::create([
                    'tanggal' => $barang->tanggal_perolehan->toDateString(), 'tipe' => 'biaya', 'kategori' => 'Pembelian inventaris',
                    'keterangan' => $barang->nama.' ('.$barang->jumlah.' '.$barang->satuan.')', 'jumlah' => $barang->nilai,
                ]);
            }
        });

        return redirect()->route('inventaris.index')->with('sukses', 'Barang inventaris disimpan.');
    }

    public function edit(Inventaris $inventaris)
    {
        return view('inventaris.form', ['barang' => $inventaris]);
    }

    public function update(Request $request, Inventaris $inventaris)
    {
        $inventaris->update($this->validasi($request));

        return redirect()->route('inventaris.index')->with('sukses', 'Data inventaris diperbarui.');
    }

    /** Menghapus catatan yang salah input. Barang yang dijual atau rusak cukup diisi tanggal dihapuskan. */
    public function destroy(Inventaris $inventaris)
    {
        $inventaris->delete();

        return redirect()->route('inventaris.index')->with('sukses', 'Catatan inventaris dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'satuan' => ['required', 'string', 'max:20'],
            'tanggal_perolehan' => ['required', 'date', 'before_or_equal:today'],
            'nilai' => ['required', 'integer', 'min:0'],
            'umur_tahun' => ['required', 'integer', 'between:0,50'],
            'kondisi' => ['required', Rule::in(array_keys(Inventaris::KONDISI))],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'tanggal_dihapus' => ['nullable', 'date', 'after_or_equal:tanggal_perolehan', 'before_or_equal:today'],
        ]);
    }
}
