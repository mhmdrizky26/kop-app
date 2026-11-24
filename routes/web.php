<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(
    auth()->user()?->halamanAwal() ?? 'login'
));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/login/pengurus', [AuthController::class, 'formPengurus'])->name('login.pengurus');
    Route::post('/login/pengurus', [AuthController::class, 'loginPengurus'])->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- Halaman pengurus
// Editor (bendahara) dan akun lihat saja (ketua, badan pemeriksa) sama-sama bisa membuka halaman ini.
Route::middleware(['auth', 'peran:pengurus'])->group(function () {
    // Mengisi, mengubah, dan menghapus data: hanya editor. Didaftarkan lebih dulu supaya
    // alamat seperti /pinjaman/baru tidak tertangkap oleh /pinjaman/{pinjaman}.
    Route::middleware('can:ubah-data')->group(function () {
        Route::post('/tagihan/bayar', [TagihanController::class, 'bayar'])->name('tagihan.bayar');
        Route::post('/tagihan/bayar-semua', [TagihanController::class, 'bayarSemua'])->name('tagihan.bayar-semua');
        Route::post('/tagihan/batal', [TagihanController::class, 'batal'])->name('tagihan.batal');

        Route::get('/transaksi/baru', [TransaksiController::class, 'create'])->name('transaksi.create');
        Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
        Route::delete('/transaksi/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

        Route::get('/pinjaman/baru', [PinjamanController::class, 'create'])->name('pinjaman.create');
        Route::post('/pinjaman', [PinjamanController::class, 'store'])->name('pinjaman.store');
        Route::post('/pinjaman/{pinjaman}/bayar', [PinjamanController::class, 'bayar'])->name('pinjaman.bayar');
        Route::delete('/pinjaman/{pinjaman}', [PinjamanController::class, 'destroy'])->name('pinjaman.destroy');

        Route::get('/anggota/baru', [AnggotaController::class, 'create'])->name('anggota.create');
        Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
        Route::get('/anggota/{anggota}/ubah', [AnggotaController::class, 'edit'])->name('anggota.edit');
        Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');

        Route::post('/kas', [KasController::class, 'store'])->name('kas.store');
        Route::delete('/kas/{kas}', [KasController::class, 'destroy'])->name('kas.destroy');

        Route::get('/inventaris/baru', [InventarisController::class, 'create'])->name('inventaris.create');
        Route::post('/inventaris', [InventarisController::class, 'store'])->name('inventaris.store');
        Route::get('/inventaris/{inventaris}/ubah', [InventarisController::class, 'edit'])->name('inventaris.edit');
        Route::put('/inventaris/{inventaris}', [InventarisController::class, 'update'])->name('inventaris.update');
        Route::delete('/inventaris/{inventaris}', [InventarisController::class, 'destroy'])->name('inventaris.destroy');
    });

    // Melihat data dan laporan: editor dan akun lihat saja.
    Route::get('/beranda', DashboardController::class)->name('dashboard');
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/pinjaman', [PinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{pinjaman}', [PinjamanController::class, 'show'])->name('pinjaman.show');
    Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
    Route::get('/anggota/{anggota}', [AnggotaController::class, 'show'])->name('anggota.show');
    Route::get('/kas', [KasController::class, 'index'])->name('kas.index');
    Route::get('/inventaris', [InventarisController::class, 'index'])->name('inventaris.index');

    Route::get('/laporan/rekap', [LaporanController::class, 'rekap'])->name('laporan.rekap');
    Route::get('/laporan/shu', [LaporanController::class, 'shu'])->name('laporan.shu');
    Route::get('/laporan/neraca', [LaporanController::class, 'neraca'])->name('laporan.neraca');
});

// ---------------------------------------------------------------- Halaman admin (akun pengurus dan pengaturan aplikasi)
Route::middleware(['auth', 'peran:admin'])->group(function () {
    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::put('/pengaturan/identitas', [PengaturanController::class, 'identitas'])->name('pengaturan.identitas');
    Route::post('/pengaturan/tahun-baru', [PengaturanController::class, 'tahunBaru'])->name('pengaturan.tahun-baru');

    Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
    Route::post('/akun', [AkunController::class, 'store'])->name('akun.store');
    Route::put('/akun/{akun}', [AkunController::class, 'update'])->name('akun.update');
    Route::delete('/akun/{akun}', [AkunController::class, 'destroy'])->name('akun.destroy');
});

// ---------------------------------------------------------------- Profil akun sendiri (admin, editor, lihat saja)
Route::middleware(['auth', 'peran:staf'])->group(function () {
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
});

// ---------------------------------------------------------------- Halaman anggota (hanya melihat data sendiri)
Route::middleware(['auth', 'peran:anggota'])->group(function () {
    Route::get('/saya', [PortalController::class, 'index'])->name('portal');
});
