<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Barang milik koperasi. Nilainya masuk ke neraca sebagai kekayaan di luar kas.
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('satuan', 20)->default('unit');
            $table->date('tanggal_perolehan');
            $table->unsignedBigInteger('nilai'); // harga perolehan seluruh jumlah
            $table->string('kondisi', 20)->default('baik'); // baik | rusak_ringan | rusak_berat
            $table->string('lokasi')->nullable();
            $table->string('keterangan')->nullable();
            $table->date('tanggal_dihapus')->nullable(); // dijual, hilang, atau dihapuskan dari buku
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
