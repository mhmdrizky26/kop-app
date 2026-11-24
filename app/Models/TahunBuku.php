<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class TahunBuku extends Model
{
    protected $table = 'tahun_buku';

    protected $guarded = ['id'];

    /** Pos pembagian SHU: kolom => nama yang tampil. */
    public const POS_SHU = [
        'shu_jasa_simpanan' => 'Jasa simpanan (dibagi ke anggota)',
        'shu_jasa_pinjaman' => 'Jasa pinjaman (dibagi ke anggota)',
        'shu_administrasi' => 'Biaya administrasi / RAT',
        'shu_pengurus' => 'Jasa pengurus',
        'shu_cadangan' => 'Cadangan',
        'shu_dana_sosial' => 'Dana sosial',
    ];

    /** Saldo awal pos neraca yang tidak berasal dari transaksi anggota: kolom => nama di neraca. */
    public const SALDO_AWAL_NERACA = [
        'awal_dana_sosial' => 'Dana sosial',
        'awal_cadangan' => 'Cadangan',
        'awal_modal_warung' => 'Modal warung KGB',
        'awal_pinjaman_luar' => 'Pinjaman di luar anggota',
        'awal_paket_munggahan' => 'Pinjaman paket munggahan',
    ];

    /** Pengesah laporan cetak dan export: kolom => jabatan. */
    public const PENGESAH = [
        'nama_ketua' => 'Ketua',
        'nama_bendahara' => 'Bendahara',
        'nama_pemeriksa_1' => 'Badan pemeriksa 1',
        'nama_pemeriksa_2' => 'Badan pemeriksa 2',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'date', 'selesai' => 'date', 'aktif' => 'boolean',
            'jasa_persen' => 'float', 'provisi_persen' => 'float',
            'shu_jasa_simpanan' => 'float', 'shu_jasa_pinjaman' => 'float', 'shu_administrasi' => 'float',
            'shu_pengurus' => 'float', 'shu_cadangan' => 'float', 'shu_dana_sosial' => 'float',
        ];
    }

    public static function aktif(): self
    {
        return static::where('aktif', true)->latest('mulai')->firstOrFail();
    }

    /** Daftar bulan (tanggal 1) dalam tahun buku ini. */
    public function bulan(): array
    {
        return array_map(
            fn ($d) => Carbon::parse($d)->startOfMonth(),
            iterator_to_array(CarbonPeriod::create($this->mulai->copy()->startOfMonth(), '1 month', $this->selesai))
        );
    }
}
