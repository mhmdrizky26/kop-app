<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Barang milik koperasi. Disusutkan garis lurus setiap bulan, mulai bulan setelah barang diperoleh,
 * sampai nilainya habis atau barang dihapuskan.
 */
class Inventaris extends Model
{
    use HasFactory;

    protected $table = 'inventaris';

    protected $fillable = [
        'nama', 'jumlah', 'satuan', 'tanggal_perolehan', 'nilai', 'umur_tahun', 'kondisi', 'lokasi', 'keterangan', 'tanggal_dihapus',
    ];

    public const KONDISI = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat'];

    /** Pilihan cepat umur pakai (tahun) => keterangan. */
    public const UMUR_PAKAI = [
        4 => '4 tahun: elektronik dan perabot kecil (laptop, printer, kipas)',
        8 => '8 tahun: perabot dan mesin besar (lemari, brankas, fotokopi)',
        0 => 'Tidak disusutkan',
    ];

    protected function casts(): array
    {
        return ['tanggal_perolehan' => 'date', 'tanggal_dihapus' => 'date', 'umur_tahun' => 'integer'];
    }

    /** Barang yang tercatat sebagai milik koperasi pada tanggal tertentu. */
    public function scopeDimilikiPada(Builder $query, Carbon $tanggal): Builder
    {
        return $query->whereDate('tanggal_perolehan', '<=', $tanggal)
            ->where(fn ($q) => $q->whereNull('tanggal_dihapus')->orWhereDate('tanggal_dihapus', '>', $tanggal));
    }

    public function getLabelKondisiAttribute(): string
    {
        return self::KONDISI[$this->kondisi] ?? $this->kondisi;
    }

    /** Total penyusutan sampai tanggal tertentu (berhenti saat barang dihapuskan). */
    public function akumulasiPenyusutan(Carbon $sampai): int
    {
        if ($this->umur_tahun <= 0) {
            return 0;
        }
        if ($this->tanggal_dihapus && $this->tanggal_dihapus->lt($sampai)) {
            $sampai = $this->tanggal_dihapus;
        }

        // Bulan perolehan tidak disusutkan; penyusutan dimulai bulan berikutnya.
        $bulan = max(0, (int) $this->tanggal_perolehan->copy()->startOfMonth()->diffInMonths($sampai->copy()->startOfMonth()));

        return (int) min($this->nilai, round($this->nilai * $bulan / ($this->umur_tahun * 12)));
    }

    /** Nilai barang sekarang: harga perolehan dikurangi penyusutan. */
    public function nilaiBuku(Carbon $sampai): int
    {
        return (int) $this->nilai - $this->akumulasiPenyusutan($sampai);
    }
}
