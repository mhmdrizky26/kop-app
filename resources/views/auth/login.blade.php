@extends('layouts.auth')

@section('judul', 'Masuk anggota')

@section('isi')
    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk anggota</h2>
    <p class="mt-1.5 text-sm text-slate-500">Masukkan nama pengguna Anda, yaitu nama Anda dengan titik.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="nama_pengguna" class="label">Nama pengguna</label>
            <input id="nama_pengguna" name="nama_pengguna" value="{{ old('nama_pengguna') }}" class="input" placeholder="contoh: muhamad.rizky" autofocus required autocomplete="username" autocapitalize="none" spellcheck="false">
            <p class="hint">Tanpa gelar. Jika lupa, tanyakan ke pengurus koperasi.</p>
            @error('nama_pengguna') <p class="galat">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="ingat" value="1" class="size-4 rounded border-slate-300 accent-blue-600"> Tetap masuk di perangkat ini
        </label>
        <button class="btn btn-primary w-full py-2.5">Masuk</button>
    </form>
@endsection
