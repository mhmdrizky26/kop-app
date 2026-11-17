<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PinjamanController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, ['aktif', 'lunas', 'semua']) ? $request->status : 'aktif';

        $pinjaman = Pinjaman::with('anggota')->denganTerbayar()
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest('tanggal')->latest('id')->get();

        $aktif = $status === 'aktif' ? $pinjaman : Pinjaman::where('status', 'aktif')->denganTerbayar()->get();

        return view('pinjaman.index', [
            'pinjaman' => $pinjaman,
            'status' => $status,
            'beredar' => $aktif->sum('sisa'),
            'jumlahAktif' => $aktif->count(),
            'angsuranBulanan' => $aktif->sum('tagihan_angsuran') + $aktif->sum('tagihan_jasa'),
        ]);
    }

    public function create(Request $request)
    {
        return view('pinjaman.create', [
            'anggota' => Anggota::aktif()->orderBy('nomor')->get(),
            'tahun' => $this->tahun(),
            'terpilih' => $request->integer('anggota') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'pokok' => ['required', 'integer', 'min:100000'],
            'tenor' => ['required', 'integer', 'between:1,120'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jasa_persen' => ['required', 'numeric', 'between:0,100'],
            'jasa_metode' => ['required', Rule::in(['tetap', 'menurun'])],
            'provisi_persen' => ['required', 'numeric', 'between:0,100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $tanggal = Carbon::parse($data['tanggal']);
        $provisi = (int) round($data['pokok'] * $data['provisi_persen'] / 100);
        // Angsuran pokok dibulatkan ke atas per seribu; kelebihannya terkoreksi pada angsuran terakhir.
        $angsuran = (int) (ceil($data['pokok'] / $data['tenor'] / 1000) * 1000);

        $pinjaman = DB::transaction(function () use ($data, $tanggal, $provisi, $angsuran, $request) {
            $pinjaman = Pinjaman::create([
                'anggota_id' => $data['anggota_id'], 'tanggal' => $tanggal->toDateString(),
                'pokok' => $data['pokok'], 'tenor' => $data['tenor'], 'angsuran_pokok' => $angsuran,
                'jasa_persen' => $data['jasa_persen'], 'jasa_metode' => $data['jasa_metode'],
                'provisi' => $provisi, 'keterangan' => $data['keterangan'] ?? null,
            ]);

            $dasar = [
                'anggota_id' => $pinjaman->anggota_id, 'pinjaman_id' => $pinjaman->id,
                'tanggal' => $tanggal->toDateString(), 'periode' => $tanggal->copy()->startOfMonth()->toDateString(),
                'sumber' => 'manual', 'user_id' => $request->user()->id,
            ];
            Transaksi::create($dasar + ['jenis' => 'pinjaman', 'jumlah' => $pinjaman->pokok]);
            if ($provisi > 0) {
                Transaksi::create($dasar + ['jenis' => 'provisi', 'jumlah' => $provisi, 'metode' => 'setor']);
            }

            return $pinjaman;
        });

        return redirect()->route('pinjaman.show', $pinjaman)->with('sukses', 'Pinjaman baru sudah dicatat.');
    }

    public function show(Pinjaman $pinjaman)
    {
        $pinjaman->load('anggota');

        return view('pinjaman.show', [
            'pinjaman' => $pinjaman,
            'riwayat' => $pinjaman->transaksi()->latest('tanggal')->latest('id')->get(),
            'jasaDibayar' => (int) $pinjaman->transaksi()->where('jenis', 'jasa')->sum('jumlah'),
        ]);
    }

    /** Pembayaran di luar tagihan bulanan, misalnya pelunasan dipercepat. */
    public function bayar(Request $request, Pinjaman $pinjaman)
    {
        $data = $request->validate([
            'angsuran' => ['required', 'integer', 'min:0', 'max:'.$pinjaman->sisa],
            'jasa' => ['nullable', 'integer', 'min:0'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'metode' => ['required', Rule::in(array_keys(Transaksi::METODE))],
        ], ['angsuran.max' => 'Angsuran melebihi sisa pinjaman ('.rupiah($pinjaman->sisa).').']);

        $jasa = (int) ($data['jasa'] ?? 0);
        if ($data['angsuran'] + $jasa <= 0) {
            return back()->with('galat', 'Isi jumlah angsuran atau jasa yang dibayar.');
        }

        $tanggal = Carbon::parse($data['tanggal']);
        $dasar = [
            'anggota_id' => $pinjaman->anggota_id, 'pinjaman_id' => $pinjaman->id,
            'tanggal' => $tanggal->toDateString(), 'periode' => $tanggal->copy()->startOfMonth()->toDateString(),
            'metode' => $data['metode'], 'sumber' => 'manual', 'user_id' => $request->user()->id,
            'keterangan' => 'Pembayaran di luar tagihan',
        ];

        DB::transaction(function () use ($data, $jasa, $dasar, $pinjaman) {
            if ($data['angsuran'] > 0) {
                Transaksi::create($dasar + ['jenis' => 'angsuran', 'jumlah' => $data['angsuran']]);
            }
            if ($jasa > 0) {
                Transaksi::create($dasar + ['jenis' => 'jasa', 'jumlah' => $jasa]);
            }
            $pinjaman->segarkanStatus();
        });

        return back()->with('sukses', 'Pembayaran pinjaman sudah dicatat.');
    }

    /** Pinjaman yang salah input boleh dihapus selama belum ada angsuran. */
    public function destroy(Pinjaman $pinjaman)
    {
        if ($pinjaman->transaksi()->whereIn('jenis', ['angsuran', 'jasa'])->exists()) {
            return back()->with('galat', 'Pinjaman yang sudah diangsur tidak bisa dihapus.');
        }

        $pinjaman->delete();

        return redirect()->route('pinjaman.index')->with('sukses', 'Pinjaman dihapus.');
    }
}
