{{-- Modal profil akun sendiri. Dibuka dari nama di sidebar; terbuka lagi otomatis jika isian ditolak. --}}
@php
    $user = auth()->user();
    $galat = $errors->profil;
    // Isian lama hanya dipakai jika yang terakhir dikirim adalah form ini, bukan form lain di halaman.
    $lama = fn (string $kunci, $bawaan) => old('_form') === 'profil' ? old($kunci, $bawaan) : $bawaan;
@endphp
<div x-show="profil" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="profil-judul">
    <div class="absolute inset-0 bg-slate-900/40" @click="profil = false" x-show="profil" x-transition.opacity></div>

    <form method="POST" action="{{ route('profil.update') }}" class="relative max-h-full w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl"
        x-show="profil" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">
        @csrf @method('PUT')
        <input type="hidden" name="_form" value="profil">

        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 id="profil-judul" class="text-base font-semibold text-slate-900">Profil Saya</h2>
                <p class="text-xs text-slate-500">Peran: {{ $user->label_peran }}. Peran hanya bisa diubah admin.</p>
            </div>
            <button type="button" class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="profil = false" aria-label="Tutup">
                <x-icon name="x" class="size-4" />
            </button>
        </div>

        <div class="space-y-4 p-5">
            <div>
                <label for="profil-nama" class="label">Nama lengkap</label>
                <input id="profil-nama" name="name" value="{{ $lama('name', $user->name) }}" class="input" maxlength="100" required>
                @if ($galat->has('name')) <p class="galat">{{ $galat->first('name') }}</p> @endif
            </div>
            <div>
                <label for="profil-username" class="label">Nama pengguna</label>
                <input id="profil-username" name="username" value="{{ $lama('username', $user->username) }}" class="input font-mono" maxlength="50" required autocomplete="username">
                <p class="hint">Dipakai untuk masuk. Huruf, angka, titik, tanda hubung, atau garis bawah.</p>
                @if ($galat->has('username')) <p class="galat">{{ $galat->first('username') }}</p> @endif
            </div>

            <div class="border-t border-slate-100 pt-4">
                <p class="text-sm font-medium text-slate-900">Ganti kata sandi</p>
                <p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengganti.</p>
                <div class="mt-3 space-y-3">
                    <div>
                        <label for="profil-sandi-lama" class="label">Kata sandi lama</label>
                        <input id="profil-sandi-lama" type="password" name="password_lama" class="input" autocomplete="current-password">
                        @if ($galat->has('password_lama')) <p class="galat">{{ $galat->first('password_lama') }}</p> @endif
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="profil-sandi" class="label">Kata sandi baru</label>
                            <input id="profil-sandi" type="password" name="password" class="input" minlength="8" autocomplete="new-password">
                        </div>
                        <div>
                            <label for="profil-sandi-ulang" class="label">Ulangi kata sandi baru</label>
                            <input id="profil-sandi-ulang" type="password" name="password_confirmation" class="input" minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    @if ($galat->has('password')) <p class="galat">{{ $galat->first('password') }}</p> @endif
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
            <button type="button" class="btn btn-ghost" @click="profil = false">Batal</button>
            <button class="btn btn-primary">Simpan profil</button>
        </div>
    </form>
</div>
