<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(
    auth()->check() ? (auth()->user()->isPengurus() ? 'dashboard' : 'portal') : 'login'
));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- Halaman pengurus
Route::middleware(['auth', 'peran:pengurus'])->group(function () {
    Route::get('/beranda', DashboardController::class)->name('dashboard');

    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/bayar', [TagihanController::class, 'bayar'])->name('tagihan.bayar');
    Route::post('/tagihan/bayar-semua', [TagihanController::class, 'bayarSemua'])->name('tagihan.bayar-semua');
    Route::post('/tagihan/batal', [TagihanController::class, 'batal'])->name('tagihan.batal');

    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/baru', [TransaksiController::class, 'create'])->name('transaksi.create');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::delete('/transaksi/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

    Route::get('/pinjaman', [PinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/baru', [PinjamanController::class, 'create'])->name('pinjaman.create');
    Route::post('/pinjaman', [PinjamanController::class, 'store'])->name('pinjaman.store');
    Route::get('/pinjaman/{pinjaman}', [PinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{pinjaman}/bayar', [PinjamanController::class, 'bayar'])->name('pinjaman.bayar');
    Route::delete('/pinjaman/{pinjaman}', [PinjamanController::class, 'destroy'])->name('pinjaman.destroy');

    Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
    Route::get('/anggota/baru', [AnggotaController::class, 'create'])->name('anggota.create');
    Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
    Route::get('/anggota/{anggota}', [AnggotaController::class, 'show'])->name('anggota.show');
    Route::get('/anggota/{anggota}/ubah', [AnggotaController::class, 'edit'])->name('anggota.edit');
    Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');
    Route::post('/anggota/{anggota}/reset-pin', [AnggotaController::class, 'resetPin'])->name('anggota.reset-pin');

    Route::get('/kas', [KasController::class, 'index'])->name('kas.index');
    Route::post('/kas', [KasController::class, 'store'])->name('kas.store');
    Route::delete('/kas/{kas}', [KasController::class, 'destroy'])->name('kas.destroy');

    Route::get('/laporan/rekap', [LaporanController::class, 'rekap'])->name('laporan.rekap');
    Route::get('/laporan/shu', [LaporanController::class, 'shu'])->name('laporan.shu');

    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::post('/pengaturan/tahun-baru', [PengaturanController::class, 'tahunBaru'])->name('pengaturan.tahun-baru');
});

// ---------------------------------------------------------------- Halaman anggota (hanya melihat data sendiri)
Route::middleware(['auth', 'peran:anggota'])->group(function () {
    Route::get('/saya', [PortalController::class, 'index'])->name('portal');
    Route::get('/saya/pin', [PortalController::class, 'pin'])->name('portal.pin');
    Route::put('/saya/pin', [PortalController::class, 'ubahPin'])->name('portal.pin.update');
});
