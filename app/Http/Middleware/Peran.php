<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Membatasi halaman untuk satu peran: pengurus atau anggota. */
class Peran
{
    public function handle(Request $request, Closure $next, string $peran): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== $peran) {
            // Pengguna dengan peran lain dikembalikan ke halaman miliknya sendiri.
            return redirect()->route($user?->isPengurus() ? 'dashboard' : 'portal');
        }

        return $next($request);
    }
}
