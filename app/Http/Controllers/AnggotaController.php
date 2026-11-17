<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, ['aktif', 'keluar', 'semua']) ? $request->status : 'aktif';

        $anggota = Anggota::query()
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->when($request->filled('cari'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', '%'.$request->cari.'%')->orWhere('nomor', $request->cari)))
            ->orderBy('nomor')->get();

        return view('anggota.index', [
            'anggota' => $anggota,
            'saldo' => Keuangan::saldo(),
            'status' => $status,
        ]);
    }

    public function create()
    {
        return view('anggota.form', [
            'anggota' => new Anggota([
                'nomor' => (int) Anggota::max('nomor') + 1,
                'tanggal_masuk' => today(),
                'status' => 'aktif',
            ]),
            'tahun' => $this->tahun(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $pin = (string) random_int(100000, 999999);
        $tahun = $this->tahun();

        $anggota = DB::transaction(function () use ($data, $pin, $request, $tahun) {
            $anggota = Anggota::create($data);

            User::create([
                'name' => $anggota->nama, 'username' => (string) $anggota->nomor,
                'password' => $pin, 'role' => 'anggota', 'anggota_id' => $anggota->id,
            ]);

            if ($request->boolean('catat_pokok') && $tahun->simpanan_pokok > 0) {
                Transaksi::create([
                    'anggota_id' => $anggota->id, 'tanggal' => $anggota->tanggal_masuk->toDateString(),
                    'periode' => $anggota->tanggal_masuk->copy()->startOfMonth()->toDateString(),
                    'jenis' => 'simpanan_pokok', 'metode' => 'setor', 'sumber' => 'manual',
                    'jumlah' => $tahun->simpanan_pokok, 'keterangan' => 'Simpanan pokok anggota baru',
                    'user_id' => $request->user()->id,
                ]);
            }

            return $anggota;
        });

        return redirect()->route('anggota.show', $anggota)
            ->with('sukses', "Anggota baru tersimpan. Login anggota: nomor {$anggota->nomor}, PIN {$pin}. Catat PIN ini, karena tidak ditampilkan lagi.");
    }

    public function show(Anggota $anggota)
    {
        $tahun = $this->tahun();

        return view('anggota.show', [
            'anggota' => $anggota,
            'tahun' => $tahun,
            'buku' => Keuangan::bukuTabungan($anggota, $tahun),
            'pinjaman' => $anggota->pinjaman()->denganTerbayar()->latest('tanggal')->get(),
            'transaksi' => $anggota->transaksi()->latest('tanggal')->latest('id')->limit(8)->get(),
        ]);
    }

    public function edit(Anggota $anggota)
    {
        return view('anggota.form', ['anggota' => $anggota, 'tahun' => $this->tahun()]);
    }

    public function update(Request $request, Anggota $anggota)
    {
        $data = $this->validasi($request, $anggota);

        DB::transaction(function () use ($anggota, $data) {
            $anggota->update($data);
            $anggota->user?->update(['name' => $anggota->nama, 'username' => (string) $anggota->nomor]);
        });

        return redirect()->route('anggota.show', $anggota)->with('sukses', 'Data anggota diperbarui.');
    }

    public function resetPin(Anggota $anggota)
    {
        $pin = (string) random_int(100000, 999999);

        User::updateOrCreate(
            ['anggota_id' => $anggota->id],
            ['name' => $anggota->nama, 'username' => (string) $anggota->nomor, 'password' => $pin, 'role' => 'anggota']
        );

        return back()->with('sukses', "PIN baru untuk {$anggota->nama}: {$pin}. Catat PIN ini, karena tidak ditampilkan lagi.");
    }

    private function validasi(Request $request, ?Anggota $anggota = null): array
    {
        $data = $request->validate([
            'nomor' => ['required', 'integer', 'min:1', Rule::unique('anggota', 'nomor')->ignore($anggota?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'tanggal_masuk' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['aktif', 'keluar'])],
            'cara_bayar' => ['required', Rule::in(['potong_gaji', 'setor'])],
            'setoran_manasuka' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['potong_gaji'] = $data['cara_bayar'] === 'potong_gaji';
        $data['setoran_manasuka'] = (int) ($data['setoran_manasuka'] ?? 0);
        $data['status'] ??= 'aktif';
        unset($data['cara_bayar']);

        return $data;
    }
}
