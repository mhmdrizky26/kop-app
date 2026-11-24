<?php

namespace App\Providers;

use App\Models\PengaturanAplikasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hanya bendahara (editor) yang boleh mengisi, mengubah, dan menghapus data. Ketua dan pemeriksa hanya melihat.
        Gate::define('ubah-data', fn (User $user): bool => $user->bisaMengubah());

        // Nama dan alamat koperasi diatur admin dari halaman Pengaturan, bukan dari file .env.
        PengaturanAplikasi::terapkan();
    }
}
