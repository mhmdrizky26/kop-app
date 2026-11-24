<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'password', 'role', 'anggota_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    /**
     * Peran akun yang masuk lewat login pengurus:
     * admin hanya mengatur akun dan pengaturan aplikasi, pengurus (bendahara) mengisi dan mengubah data,
     * pengawas (ketua dan badan pemeriksa) hanya melihat data dan laporan.
     */
    public const PERAN_PENGURUS = ['admin' => 'Admin', 'pengurus' => 'Editor', 'pengawas' => 'Lihat saja'];

    /** Bisa membuka halaman data dan laporan (editor maupun lihat saja). */
    public function isPengurus(): bool
    {
        return in_array($this->role, ['pengurus', 'pengawas'], true);
    }

    /** Mengatur akun pengurus dan pengaturan aplikasi. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Nama rute halaman pertama setelah masuk, sesuai peran. */
    public function halamanAwal(): string
    {
        return match (true) {
            $this->isAdmin() => 'pengaturan.edit',
            $this->isPengurus() => 'dashboard',
            default => 'portal',
        };
    }

    /** Bisa menyimpan, mengubah, dan menghapus data. */
    public function bisaMengubah(): bool
    {
        return $this->role === 'pengurus';
    }

    public function getLabelPeranAttribute(): string
    {
        return self::PERAN_PENGURUS[$this->role] ?? 'Anggota';
    }

    /**
     * Nama pengguna anggota dari namanya: "Muhamad Rizky, S.Pd." menjadi "muhamad.rizky".
     * Huruf kecil, gelar setelah koma dibuang, spasi dan tanda baca menjadi titik.
     * Dipakai juga untuk merapikan ketikan saat masuk, jadi "Muhamad Rizky" dan "muhamad.rizky" sama-sama cocok.
     */
    public static function namaPenggunaDariNama(string $nama): string
    {
        return (string) Str::of(Str::before($nama, ','))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.');
    }

    /** Nama pengguna yang belum dipakai akun lain: muhamad.rizky, lalu muhamad.rizky2, muhamad.rizky3, dan seterusnya. */
    public static function namaPenggunaUnik(string $nama, ?int $kecualiId = null): string
    {
        $dasar = static::namaPenggunaDariNama($nama) ?: 'anggota';
        $calon = $dasar;
        for ($urutan = 2; static::where('username', $calon)->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))->exists(); $urutan++) {
            $calon = $dasar.$urutan;
        }

        return $calon;
    }
}
