<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Membatasi halaman untuk satu peran: admin, pengurus (termasuk akun lihat saja), staf (semua akun pengurus), atau anggota. */
class Peran
{
    public function handle(Request $request, Closure $next, string $peran): Response
    {
        $user = $request->user();
        $cocok = match ($peran) {
            'pengurus' => $user?->isPengurus(),
            'admin' => $user?->isAdmin(),
            'staf' => $user && array_key_exists($user->role, User::PERAN_PENGURUS),
            default => $user?->role === $peran,
        };

        if (! $cocok) {
            // Pengguna dengan peran lain dikembalikan ke halaman miliknya sendiri.
            return redirect()->route($user?->halamanAwal() ?? 'login');
        }

        return $next($request);
    }
}
