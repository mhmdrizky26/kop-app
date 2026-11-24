<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Identitas koperasi yang diatur admin dari aplikasi. Nilainya menimpa config saat aplikasi berjalan,
 * jadi semua tampilan yang memakai config('app.name') dan config('koperasi.*') ikut berubah.
 */
class PengaturanAplikasi extends Model
{
    protected $table = 'pengaturan_aplikasi';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    /** Kunci yang bisa diatur => [kunci config yang ditimpa, label]. */
    public const IDENTITAS = [
        'nama_aplikasi' => ['app.name', 'Nama aplikasi'],
        'nama_koperasi' => ['koperasi.nama', 'Nama koperasi (kop laporan)'],
        'alamat' => ['koperasi.alamat', 'Alamat'],
        'kota' => ['koperasi.kota', 'Kota (untuk tanggal tanda tangan)'],
    ];

    /** Menimpa config dengan nilai yang tersimpan. Diam saja jika tabel belum ada (sebelum migrasi). */
    public static function terapkan(): void
    {
        try {
            $tersimpan = static::pluck('nilai', 'kunci');
        } catch (QueryException) {
            return;
        }

        foreach (self::IDENTITAS as $kunci => [$config]) {
            if (filled($tersimpan[$kunci] ?? null)) {
                config([$config => $tersimpan[$kunci]]);
            }
        }
    }

    /** Nilai yang berlaku sekarang: yang tersimpan, atau bawaan dari config/.env. */
    public static function nilaiSekarang(): array
    {
        return collect(self::IDENTITAS)->map(fn ($isi) => config($isi[0]))->all();
    }
}
