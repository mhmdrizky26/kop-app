<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Umur pakai untuk penyusutan garis lurus. 0 berarti tidak disusutkan.
        Schema::table('inventaris', function (Blueprint $table) {
            $table->unsignedTinyInteger('umur_tahun')->default(4)->after('nilai');
        });
    }

    public function down(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            $table->dropColumn('umur_tahun');
        });
    }
};
