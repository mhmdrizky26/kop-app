<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cara bayar tidak lagi satu pilihan untuk semua setoran, tetapi diatur per bagian tagihan
 * (simpanan wajib, simpanan manasuka, angsuran + jasa pinjaman), supaya anggota bisa membayar campuran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->string('bayar_wajib', 20)->default('setor')->after('potong_gaji'); // potong_gaji | setor
            $table->string('bayar_manasuka', 20)->default('setor')->after('bayar_wajib');
            $table->string('bayar_pinjaman', 20)->default('setor')->after('bayar_manasuka');
        });

        DB::table('anggota')->where('potong_gaji', true)->update([
            'bayar_wajib' => 'potong_gaji', 'bayar_manasuka' => 'potong_gaji', 'bayar_pinjaman' => 'potong_gaji',
        ]);

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn('potong_gaji');
        });
    }

    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->boolean('potong_gaji')->default(false)->after('tanggal_masuk');
        });

        DB::table('anggota')->where('bayar_wajib', 'potong_gaji')->update(['potong_gaji' => true]);

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn(['bayar_wajib', 'bayar_manasuka', 'bayar_pinjaman']);
        });
    }
};
