<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    public function isPengurus(): bool
    {
        return $this->role === 'pengurus';
    }

    /** Menyamakan ketikan anggota dengan ID tersimpan: huruf besar, tanpa spasi atau tanda hubung. */
    public static function rapikanIdAnggota(string $id): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $id));
    }
}
