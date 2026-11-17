<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Services\Keuangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    /** Jenis yang bisa dicatat dari form: setoran dan penarikan simpanan. */
    private const JENIS_FORM = [
        'simpanan_manasuka', 'simpanan_wajib', 'simpanan_pokok', 'tarik_manasuka', 'tarik_wajib', 'tarik_pokok',
    ];

    public function index(Request $request)
    {
        $transaksi = Transaksi::with('anggota')
            ->when($request->filled('bulan'), fn ($q) => $q->whereDate('periode', $this->periode($request)))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->when($request->filled('cari'), fn ($q) => $q->whereHas('anggota', fn ($a) => $a
                ->where('nama', 'like', '%'.$request->cari.'%')->orWhere('nomor', $request->cari)))
            ->latest('tanggal')->latest('id')
            ->paginate(20)->withQueryString();

        return view('transaksi.index', ['transaksi' => $transaksi]);
    }

    public function create(Request $request)
    {
        return view('transaksi.create', [
            'anggota' => Anggota::aktif()->orderBy('nomor')->get(),
            'saldo' => Keuangan::saldo(),
            'jenis' => collect(self::JENIS_FORM)->mapWithKeys(fn ($j) => [$j => Transaksi::JENIS[$j]]),
            'terpilih' => $request->integer('anggota') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'jenis' => ['required', Rule::in(self::JENIS_FORM)],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'metode' => ['nullable', Rule::in(array_keys(Transaksi::METODE))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Penarikan tidak boleh melebihi saldo simpanan yang ditarik.
        if (str_starts_with($data['jenis'], 'tarik_')) {
            $kunci = substr($data['jenis'], 6);
            $saldo = Keuangan::saldoAnggota((int) $data['anggota_id'])[$kunci];
            if ($data['jumlah'] > $saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Penarikan melebihi saldo. Saldo simpanan '.$kunci.' saat ini '.rupiah($saldo).'.',
                ]);
            }
            $data['metode'] = null;
        }

        $tanggal = Carbon::parse($data['tanggal']);
        Transaksi::create($data + [
            'periode' => $tanggal->copy()->startOfMonth()->toDateString(),
            'sumber' => 'manual',
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('transaksi.index')->with('sukses', 'Transaksi sudah dicatat.');
    }

    public function destroy(Transaksi $transaksi)
    {
        abort_unless($transaksi->bisa_dihapus, 403);

        $pinjaman = $transaksi->pinjaman;
        $transaksi->delete();
        $pinjaman?->segarkanStatus();

        return back()->with('sukses', 'Transaksi dihapus.');
    }
}
