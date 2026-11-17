<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasLain extends Model
{
    protected $table = 'kas_lain';

    protected $fillable = ['tanggal', 'tipe', 'kategori', 'keterangan', 'jumlah'];

    public const KATEGORI = [
        'pendapatan' => ['Laba warung koperasi', 'Pendapatan lain-lain'],
        'biaya' => ['Konsumsi rapat', 'Alat tulis dan fotokopi', 'Transportasi', 'Perawatan inventaris', 'Dana sosial', 'Biaya lain-lain'],
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }
}
