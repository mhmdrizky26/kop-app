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

    /**
     * Bagian tagihan yang cara bayarnya bisa diatur, beserta kolom pengaturannya.
     * Angsuran dan jasa pinjaman selalu dibayar dengan cara yang sama.
     *
     * @var array<string, array{kolom: string, label: string}>
     */
    public const CARA_BAYAR_BAGIAN = [
        'wajib' => ['kolom' => 'bayar_wajib', 'label' => 'Simpanan wajib'],
        'manasuka' => ['kolom' => 'bayar_manasuka', 'label' => 'Simpanan manasuka'],
        'pinjaman' => ['kolom' => 'bayar_pinjaman', 'label' => 'Angsuran + jasa pinjaman'],
    ];

    protected $fillable = [
        'nomor', 'nama', 'jabatan', 'no_hp', 'status', 'tanggal_masuk', 'setoran_manasuka',
        'bayar_wajib', 'bayar_manasuka', 'bayar_pinjaman',
    ];

    protected function casts(): array
    {
        return ['tanggal_masuk' => 'date'];
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

    /** Cara bayar bawaan (potong_gaji atau setor) untuk satu pos tagihan: pokok, wajib, manasuka, angsuran, jasa. */
    public function caraBayar(string $pos): string
    {
        return match ($pos) {
            'wajib' => $this->bayar_wajib,
            'manasuka' => $this->bayar_manasuka,
            'angsuran', 'jasa' => $this->bayar_pinjaman,
            default => 'setor',
        } ?? 'setor';
    }

    /** Ringkasan cara bayar untuk ditampilkan: "Potong gaji", "Setor langsung", atau "Campuran". */
    public function getLabelCaraBayarAttribute(): string
    {
        $cara = array_unique([$this->bayar_wajib, $this->bayar_manasuka, $this->bayar_pinjaman]);

        return count($cara) > 1 ? 'Campuran' : (Transaksi::METODE[$cara[0]] ?? 'Setor langsung');
    }

    /** True jika ada bagian tagihan yang dibayar lewat potong gaji. */
    public function getAdaPotongGajiAttribute(): bool
    {
        return in_array('potong_gaji', [$this->bayar_wajib, $this->bayar_manasuka, $this->bayar_pinjaman], true);
    }

    /** ID anggota yang dipakai di semua tampilan, misalnya "KGB001". Untuk masuk, anggota memakai nama pengguna dari namanya. */
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
