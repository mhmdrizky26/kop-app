<?php

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Anggota tidak lagi masuk dengan ID (KGB001), tetapi dengan nama pengguna dari namanya (muhamad.rizky).
 * Kolomnya tetap; hanya isinya yang diganti untuk akun anggota yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $akun = DB::table('users')->join('anggota', 'anggota.id', '=', 'users.anggota_id')
            ->where('users.role', 'anggota')->orderBy('anggota.nomor')->get(['users.id', 'anggota.nama']);

        foreach ($akun as $a) {
            DB::table('users')->where('id', $a->id)->update(['username' => User::namaPenggunaUnik($a->nama, $a->id)]);
        }
    }

    public function down(): void
    {
        $akun = DB::table('users')->join('anggota', 'anggota.id', '=', 'users.anggota_id')
            ->where('users.role', 'anggota')->get(['users.id', 'anggota.nomor']);

        foreach ($akun as $a) {
            DB::table('users')->where('id', $a->id)->update(['username' => Anggota::kodeDariNomor($a->nomor)]);
        }
    }
};
