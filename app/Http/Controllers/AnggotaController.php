<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, ['aktif', 'keluar', 'semua']) ? $request->status : 'aktif';

        $anggota = Anggota::query()
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->when($request->filled('cari'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', '%'.$request->cari.'%')->orWhere('nomor', Anggota::nomorDariKode($request->cari))))
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
                'nomor' => Anggota::nomorBerikutnya(),
                'tanggal_masuk' => today(),
                'status' => 'aktif',
            ]),
            'tahun' => $this->tahun(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $tahun = $this->tahun();

        $anggota = DB::transaction(function () use ($data, $request, $tahun) {
            $anggota = Anggota::create($data + ['nomor' => Anggota::nomorBerikutnya()]);

            User::create([
                'name' => $anggota->nama, 'username' => User::namaPenggunaUnik($anggota->nama),
                'password' => Str::random(40), 'role' => 'anggota', 'anggota_id' => $anggota->id,
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
            ->with('sukses', "Anggota baru tersimpan. Nama pengguna untuk masuk: {$anggota->user->username}.");
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

        $namaLama = $anggota->nama;
        DB::transaction(function () use ($anggota, $data, $namaLama) {
            $anggota->update($data);
            $user = $anggota->user;
            // Nama pengguna mengikuti nama; hanya dibuat ulang jika namanya berubah.
            $user?->update(['name' => $anggota->nama] + ($anggota->nama !== $namaLama
                ? ['username' => User::namaPenggunaUnik($anggota->nama, $user->id)]
                : []));
        });

        $pesan = $anggota->nama !== $namaLama
            ? 'Data anggota diperbarui. Nama pengguna untuk masuk sekarang: '.$anggota->user?->username.'.'
            : 'Data anggota diperbarui.';

        return redirect()->route('anggota.show', $anggota)->with('sukses', $pesan);
    }

    private function validasi(Request $request, ?Anggota $anggota = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'tanggal_masuk' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['aktif', 'keluar'])],
            'bayar_wajib' => ['required', Rule::in(array_keys(Transaksi::METODE))],
            'bayar_manasuka' => ['required', Rule::in(array_keys(Transaksi::METODE))],
            'bayar_pinjaman' => ['required', Rule::in(array_keys(Transaksi::METODE))],
            'setoran_manasuka' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['setoran_manasuka'] = (int) ($data['setoran_manasuka'] ?? 0);
        $data['status'] ??= 'aktif';

        return $data;
    }
}
