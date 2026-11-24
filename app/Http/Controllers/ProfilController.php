<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Profil akun pengurus sendiri (nama, nama pengguna, kata sandi), diisi lewat modal di sidebar.
 * Galat disimpan di kantong "profil" supaya modal terbuka lagi dan tidak tercampur dengan form lain. Peran tetap diatur admin.
 */
class ProfilController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('profil', [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'password_lama' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required_with' => 'Isi kata sandi lama untuk mengganti kata sandi.',
            'password_lama.current_password' => 'Kata sandi lama tidak cocok.',
        ]);

        $user->update(['name' => $data['name'], 'username' => $data['username']] + array_filter(['password' => $data['password'] ?? null]));

        return back()->with('sukses', 'Profil disimpan.');
    }
}
