<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Anggota extends Model
{
    /** Awalan ID anggota, singkatan Koperasi Guru Bahagia. */
    public const AWALAN_ID = 'KGB';

    protected $table = 'anggota';

    protected $fillable = [
        'nomor', 'nama', 'jabatan', 'no_hp', 'status', 'tanggal_masuk', 'potong_gaji', 'setoran_manasuka',
    ];

    protected function casts(): array
    {
        return ['tanggal_masuk' => 'date', 'potong_gaji' => 'boolean'];
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    public function pinjamanAktif(): HasMany
    {
        return $this->hasMany(Pinjaman::class)->where('status', 'aktif')->denganTerbayar();
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /** ID anggota yang dipakai di semua tampilan dan untuk login, misalnya "KGB001". */
    public function getKodeAttribute(): string
    {
        return static::kodeDariNomor($this->nomor);
    }

    public static function kodeDariNomor(int $nomor): string
    {
        return static::AWALAN_ID.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT);
    }

    /** Mengubah ketikan seperti "kgb005", "KGB-005", atau "5" menjadi nomor urut 5. */
    public static function nomorDariKode(string $kode): ?int
    {
        $angka = preg_replace('/^'.static::AWALAN_ID.'/i', '', preg_replace('/[^A-Za-z0-9]/', '', trim($kode)));

        return ctype_digit($angka) ? (int) $angka : null;
    }

    /** Nomor urut berikutnya untuk anggota baru. */
    public static function nomorBerikutnya(): int
    {
        return (int) static::max('nomor') + 1;
    }

    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama));

        return mb_strtoupper(mb_substr($kata[0], 0, 1).(isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    }
}
