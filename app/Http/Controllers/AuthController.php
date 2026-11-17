<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $masuk = Auth::attempt(
            ['username' => trim($data['username']), 'password' => $data['password']],
            $request->boolean('ingat')
        );

        if (! $masuk) {
            throw ValidationException::withMessages([
                'username' => 'Nomor anggota atau PIN yang dimasukkan tidak cocok.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route(Auth::user()->isPengurus() ? 'dashboard' : 'portal');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
