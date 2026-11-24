<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identitas koperasi yang diatur admin dari aplikasi, menggantikan isian di file .env.
        Schema::create('pengaturan_aplikasi', function (Blueprint $table) {
            $table->string('kunci', 50)->primary();
            $table->string('nilai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_aplikasi');
    }
};
