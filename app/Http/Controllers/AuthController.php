<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Halaman awal: form masuk anggota. */
    public function form()
    {
        return view('auth.login');
    }

    public function formPengurus()
    {
        return view('auth.login-pengurus');
    }

    /** Anggota cukup memasukkan nama penggunanya, misalnya "muhamad.rizky" (atau namanya, "Muhamad Rizky"). */
    public function login(Request $request)
    {
        $data = $request->validate(['nama_pengguna' => ['required', 'string', 'max:100']]);

        $user = User::where('role', 'anggota')
            ->where('username', User::namaPenggunaDariNama($data['nama_pengguna']))
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'nama_pengguna' => 'Nama pengguna tidak ditemukan. Periksa kembali atau tanyakan ke pengurus.',
            ]);
        }

        Auth::login($user, $request->boolean('ingat'));
        $request->session()->regenerate();

        return redirect()->route('portal');
    }

    public function loginPengurus(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $masuk = Auth::attempt(
            [
                'username' => trim($data['username']), 'password' => $data['password'],
                fn ($query) => $query->whereIn('role', array_keys(User::PERAN_PENGURUS)),
            ],
            $request->boolean('ingat')
        );

        if (! $masuk) {
            throw ValidationException::withMessages([
                'username' => 'Nama pengguna atau kata sandi tidak cocok.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route($request->user()->halamanAwal());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
