@extends('layouts.app')

@section('judul', 'Akun Pengurus')
@section('keterangan', 'Admin mengatur akun dan pengaturan aplikasi. Editor (bendahara) mengisi dan mengubah data. Lihat saja (ketua, badan pemeriksa) hanya membuka data dan laporan.')

@section('isi')
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2" x-data="{ buka: null }">
            <div class="card-head"><h2 class="card-title">Daftar akun</h2></div>
            @foreach ($akun as $a)
                <div class="border-t border-slate-100 first:border-t-0">
                    <div class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($a->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $a->name }} @if ($a->is(auth()->user())) <span class="text-xs font-normal text-slate-400">(Anda)</span> @endif</p>
                            <p class="text-xs text-slate-500">Nama pengguna: <span class="font-mono">{{ $a->username }}</span></p>
                        </div>
                        <span @class(['badge', 'badge-amber' => $a->isAdmin(), 'badge-blue' => $a->bisaMengubah(), 'badge-slate' => $a->role === 'pengawas'])>{{ $a->label_peran }}</span>
                        <button type="button" class="btn btn-sm btn-ghost" @click="buka = buka === {{ $a->id }} ? null : {{ $a->id }}"><x-icon name="edit" class="size-3.5" /> Ubah</button>
                    </div>

                    <div x-show="buka === {{ $a->id }}" x-cloak class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                        <form method="POST" action="{{ route('akun.update', $a) }}" class="grid gap-3 sm:grid-cols-3 sm:items-end">
                            @csrf @method('PUT')
                            <div>
                                <label class="label">Peran</label>
                                <select name="role" class="input" @disabled($a->is(auth()->user()))>
                                    @foreach (\App\Models\User::PERAN_PENGURUS as $kunci => $label)
                                        <option value="{{ $kunci }}" @selected($a->role === $kunci)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($a->is(auth()->user()))
                                    <input type="hidden" name="role" value="{{ $a->role }}">
                                @endif
                            </div>
                            <div>
                                <label class="label">Kata sandi baru <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                                <input type="password" name="password" class="input" minlength="8" autocomplete="new-password">
                            </div>
                            <button class="btn btn-primary">Simpan</button>
                        </form>
                        @unless ($a->is(auth()->user()))
                            <form method="POST" action="{{ route('akun.destroy', $a) }}" class="mt-3" onsubmit="return confirm(@js('Hapus akun '.$a->name.'?'))">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger"><x-icon name="trash" class="size-3.5" /> Hapus akun</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('akun.store') }}" class="card p-5">
            @csrf
            <h2 class="card-title">Akun baru</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="name" class="label">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="input" maxlength="100" required placeholder="Contoh: Ketua koperasi">
                    @error('name') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username" class="label">Nama pengguna</label>
                    <input id="username" name="username" value="{{ old('username') }}" class="input font-mono" maxlength="50" required autocomplete="off" placeholder="contoh: ketua">
                    <p class="hint">Dipakai untuk masuk. Huruf, angka, titik, tanda hubung, atau garis bawah.</p>
                    @error('username') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <input id="password" type="password" name="password" class="input" minlength="8" required autocomplete="new-password">
                    <p class="hint">Minimal 8 karakter.</p>
                    @error('password') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="label">Peran</span>
                    <div class="grid gap-2">
                        @foreach (\App\Models\User::PERAN_PENGURUS as $kunci => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2 text-sm has-checked:border-blue-500 has-checked:bg-blue-50 has-checked:text-blue-800">
                                <input type="radio" name="role" value="{{ $kunci }}" class="size-4 accent-blue-600" @checked(old('role', 'pengawas') === $kunci)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <button class="btn btn-primary mt-5 w-full">Buat akun</button>
        </form>
    </div>
@endsection
