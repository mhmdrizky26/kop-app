<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Akun pengurus, diatur oleh admin: admin, bendahara sebagai editor,
 * serta ketua dan badan pemeriksa sebagai akun lihat saja.
 */
class AkunController extends Controller
{
    public function index()
    {
        return view('akun.index', [
            'akun' => User::whereIn('role', array_keys(User::PERAN_PENGURUS))
                ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'pengurus' THEN 1 ELSE 2 END")->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::PERAN_PENGURUS))],
        ]);

        User::create($data);

        return back()->with('sukses', "Akun {$data['name']} dibuat. Masuk lewat halaman login pengurus dengan nama pengguna {$data['username']}.");
    }

    /** Mengganti peran dan/atau kata sandi. */
    public function update(Request $request, User $akun)
    {
        abort_unless(array_key_exists($akun->role, User::PERAN_PENGURUS), 404);

        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::PERAN_PENGURUS))],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($data['role'] !== $akun->role) {
            $this->pastikanMasihAdaAdmin($akun, 'Peran tidak bisa diubah');
        }

        $akun->update(['role' => $data['role']] + array_filter(['password' => $data['password'] ?? null]));

        return back()->with('sukses', "Akun {$akun->name} diperbarui.");
    }

    public function destroy(Request $request, User $akun)
    {
        abort_unless(array_key_exists($akun->role, User::PERAN_PENGURUS), 404);

        if ($akun->is($request->user())) {
            throw ValidationException::withMessages(['akun' => 'Akun yang sedang dipakai tidak bisa dihapus.']);
        }
        $this->pastikanMasihAdaAdmin($akun, 'Akun tidak bisa dihapus');

        $akun->delete();

        return back()->with('sukses', "Akun {$akun->name} dihapus.");
    }

    /** Harus selalu ada minimal satu admin, supaya akun dan pengaturan tetap bisa diurus. */
    private function pastikanMasihAdaAdmin(User $akun, string $pesan): void
    {
        if ($akun->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages(['akun' => $pesan.': harus ada minimal satu akun admin.']);
        }
    }
}
