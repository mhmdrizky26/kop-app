<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasLain extends Model
{
    protected $table = 'kas_lain';

    protected $fillable = ['tanggal', 'tipe', 'kategori', 'keterangan', 'jumlah'];

    /** Kategori mengikuti buku KGB (sheet BOP dan NERACA). */
    public const KATEGORI = [
        'pendapatan' => [
            'Laba warung KGB', 'PSAS kelas 7', 'PSAS kelas 8-9', 'Fotokopi', 'Unit jual beli',
            'Pengembalian modal warung KGB', 'Pengembalian pinjaman di luar anggota', 'Pendapatan lain-lain',
        ],
        'biaya' => [
            'Konsumsi rapat', 'Transportasi', 'Servis dan perawatan inventaris', 'Perlengkapan kantor',
            'Alat tulis dan fotokopi', 'Rekap buku kas', 'Modal warung KGB', 'Pinjaman di luar anggota',
            'Pinjaman paket munggahan', 'Pembelian inventaris', 'Dana sosial', 'Biaya lain-lain',
        ],
    ];

    /**
     * Perpindahan modal atau dana, bukan pendapatan/biaya usaha, sehingga tidak dihitung dalam SHU (seperti di NERACA KGB).
     * "Pinjaman paket munggahan" adalah pembelian paket; tahun berikutnya otomatis dibebankan ke SHU sebagai
     * "Paket munggahan tahun lalu", jadi tidak perlu dicatat pelunasannya. "Pengembalian pinjaman paket munggahan"
     * tidak bisa dipilih lagi dan hanya tersisa di sini untuk catatan lama.
     */
    public const DI_LUAR_SHU = [
        'Pengembalian modal warung KGB', 'Pengembalian pinjaman di luar anggota', 'Pengembalian pinjaman paket munggahan',
        'Modal warung KGB', 'Pinjaman di luar anggota', 'Pinjaman paket munggahan', 'Pembelian inventaris', 'Dana sosial',
    ];

    public function getMasukShuAttribute(): bool
    {
        return ! in_array($this->kategori, self::DI_LUAR_SHU, true);
    }

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }
}
