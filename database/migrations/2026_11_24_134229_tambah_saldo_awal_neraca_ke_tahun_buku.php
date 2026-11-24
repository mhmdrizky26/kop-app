<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saldo awal pos neraca yang tidak tercatat lewat transaksi anggota, dan nama pengesah neraca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_buku', function (Blueprint $table) {
            $table->unsignedBigInteger('awal_dana_sosial')->default(0);
            $table->unsignedBigInteger('awal_cadangan')->default(0);
            $table->unsignedBigInteger('awal_modal_warung')->default(0);
            $table->unsignedBigInteger('awal_pinjaman_luar')->default(0);
            $table->unsignedBigInteger('awal_paket_munggahan')->default(0);
            $table->string('nama_ketua')->nullable();
            $table->string('nama_bendahara')->nullable();
            $table->string('nama_pemeriksa_1')->nullable();
            $table->string('nama_pemeriksa_2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tahun_buku', function (Blueprint $table) {
            $table->dropColumn([
                'awal_dana_sosial', 'awal_cadangan', 'awal_modal_warung', 'awal_pinjaman_luar', 'awal_paket_munggahan',
                'nama_ketua', 'nama_bendahara', 'nama_pemeriksa_1', 'nama_pemeriksa_2',
            ]);
        });
    }
};
