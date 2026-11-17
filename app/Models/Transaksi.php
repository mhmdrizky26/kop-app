<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'anggota_id', 'pinjaman_id', 'tanggal', 'periode', 'jenis',
        'metode', 'sumber', 'jumlah', 'keterangan', 'user_id',
    ];

    /** Daftar jenis transaksi: nama yang tampil dan arah uangnya terhadap kas koperasi. */
    public const JENIS = [
        'simpanan_pokok' => ['label' => 'Simpanan pokok', 'arah' => 'masuk'],
        'simpanan_wajib' => ['label' => 'Simpanan wajib', 'arah' => 'masuk'],
        'simpanan_manasuka' => ['label' => 'Simpanan manasuka', 'arah' => 'masuk'],
        'tarik_pokok' => ['label' => 'Tarik simpanan pokok', 'arah' => 'keluar'],
        'tarik_wajib' => ['label' => 'Tarik simpanan wajib', 'arah' => 'keluar'],
        'tarik_manasuka' => ['label' => 'Tarik simpanan manasuka', 'arah' => 'keluar'],
        'pinjaman' => ['label' => 'Pencairan pinjaman', 'arah' => 'keluar'],
        'angsuran' => ['label' => 'Angsuran pinjaman', 'arah' => 'masuk'],
        'jasa' => ['label' => 'Jasa pinjaman', 'arah' => 'masuk'],
        'provisi' => ['label' => 'Provisi', 'arah' => 'masuk'],
    ];

    public const METODE = ['potong_gaji' => 'Potong gaji', 'setor' => 'Setor langsung'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'periode' => 'date'];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    public static function jenisArah(string $arah): array
    {
        return array_keys(array_filter(self::JENIS, fn ($j) => $j['arah'] === $arah));
    }

    public function getLabelAttribute(): string
    {
        return self::JENIS[$this->jenis]['label'] ?? $this->jenis;
    }

    public function getArahAttribute(): string
    {
        return self::JENIS[$this->jenis]['arah'] ?? 'masuk';
    }

    public function getLabelMetodeAttribute(): ?string
    {
        return self::METODE[$this->metode] ?? null;
    }

    /** Transaksi yang boleh dihapus satu per satu dari daftar transaksi. */
    public function getBisaDihapusAttribute(): bool
    {
        return $this->sumber === 'manual' && ! in_array($this->jenis, ['pinjaman', 'provisi']);
    }
}
