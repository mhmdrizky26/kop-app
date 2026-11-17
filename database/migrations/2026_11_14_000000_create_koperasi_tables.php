<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tahun buku sekaligus menyimpan aturan hitung yang berlaku pada tahun itu.
        Schema::create('tahun_buku', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('mulai');
            $table->date('selesai');
            $table->boolean('aktif')->default(false);
            $table->unsignedBigInteger('simpanan_pokok')->default(5000);
            $table->unsignedBigInteger('simpanan_wajib')->default(100000);
            $table->decimal('jasa_persen', 5, 2)->default(1);
            $table->string('jasa_metode', 20)->default('tetap'); // tetap | menurun
            $table->decimal('provisi_persen', 5, 2)->default(0.5);
            $table->decimal('shu_jasa_simpanan', 5, 2)->default(30);
            $table->decimal('shu_jasa_pinjaman', 5, 2)->default(43);
            $table->decimal('shu_administrasi', 5, 2)->default(6);
            $table->decimal('shu_pengurus', 5, 2)->default(15);
            $table->decimal('shu_cadangan', 5, 2)->default(2);
            $table->decimal('shu_dana_sosial', 5, 2)->default(4);
            $table->timestamps();
        });

        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nomor')->unique();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('status', 20)->default('aktif'); // aktif | keluar
            $table->date('tanggal_masuk');
            $table->boolean('potong_gaji')->default(false);
            $table->unsignedBigInteger('setoran_manasuka')->default(0);
            $table->timestamps();
        });

        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('pokok');
            $table->unsignedSmallInteger('tenor');
            $table->unsignedBigInteger('angsuran_pokok');
            // Tarif disalin ke pinjaman supaya perubahan pengaturan tidak mengubah pinjaman berjalan.
            $table->decimal('jasa_persen', 5, 2);
            $table->string('jasa_metode', 20);
            $table->unsignedBigInteger('provisi')->default(0);
            $table->string('status', 20)->default('aktif'); // aktif | lunas
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // Buku transaksi: satu baris per uang masuk atau keluar. Semua saldo dihitung dari sini.
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->foreignId('pinjaman_id')->nullable()->constrained('pinjaman')->cascadeOnDelete();
            $table->date('tanggal');
            $table->date('periode'); // tanggal 1 pada bulan transaksi
            $table->string('jenis', 30);
            $table->string('metode', 20)->nullable(); // potong_gaji | setor
            $table->string('sumber', 20)->default('manual'); // tagihan | manual | saldo_awal
            $table->unsignedBigInteger('jumlah');
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
            $table->index(['anggota_id', 'periode']);
            $table->index(['periode', 'sumber']);
            $table->index('tanggal');
        });

        Schema::create('kas_lain', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('tipe', 20); // pendapatan | biaya
            $table->string('kategori');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('jumlah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_lain');
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('pinjaman');
        Schema::dropIfExists('anggota');
        Schema::dropIfExists('tahun_buku');
    }
};
