<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{
    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id', 'tanggal', 'pokok', 'tenor', 'angsuran_pokok',
        'jasa_persen', 'jasa_metode', 'provisi', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jasa_persen' => 'float'];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    /** Memuat total angsuran pokok yang sudah dibayar sebagai kolom "terbayar". */
    public function scopeDenganTerbayar($query)
    {
        return $query->withSum(['transaksi as terbayar' => fn ($q) => $q->where('jenis', 'angsuran')], 'jumlah');
    }

    public function getTerbayarAttribute($nilai): int
    {
        if (array_key_exists('terbayar', $this->attributes)) {
            return (int) $nilai;
        }

        return (int) $this->transaksi()->where('jenis', 'angsuran')->sum('jumlah');
    }

    public function getSisaAttribute(): int
    {
        return max(0, (int) $this->pokok - $this->terbayar);
    }

    /** Angsuran pokok yang ditagihkan bulan ini (tidak melebihi sisa pinjaman). */
    public function getTagihanAngsuranAttribute(): int
    {
        return min((int) $this->angsuran_pokok, $this->sisa);
    }

    /** Jasa bulan ini: tetap dari pokok awal, atau menurun mengikuti sisa pinjaman. */
    public function getTagihanJasaAttribute(): int
    {
        $dasar = $this->jasa_metode === 'menurun' ? $this->sisa : (int) $this->pokok;

        return $this->sisa > 0 ? (int) round($dasar * $this->jasa_persen / 100) : 0;
    }

    public function getPersenLunasAttribute(): int
    {
        return $this->pokok > 0 ? (int) min(100, round($this->terbayar / $this->pokok * 100)) : 0;
    }

    /** Menyesuaikan status aktif/lunas setelah ada pembayaran atau pembatalan. */
    public function segarkanStatus(): void
    {
        unset($this->attributes['terbayar']);
        $status = $this->sisa <= 0 ? 'lunas' : 'aktif';
        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
