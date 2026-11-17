<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Anggota extends Model
{
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

    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama));

        return mb_strtoupper(mb_substr($kata[0], 0, 1).(isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    }
}
