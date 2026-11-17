@extends('layouts.auth')

@section('judul', 'Masuk pengurus')

@section('isi')
    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk pengurus</h2>
    <p class="mt-1.5 text-sm text-slate-500">Khusus pengurus koperasi, dengan nama pengguna dan kata sandi.</p>

    <form method="POST" action="{{ route('login.pengurus') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="username" class="label">Nama pengguna</label>
            <input id="username" name="username" value="{{ old('username') }}" class="input" autofocus required autocomplete="username">
            @error('username') <p class="galat">{{ $message }}</p> @enderror
        </div>
        <div x-data="{ lihat: false }">
            <label for="password" class="label">Kata sandi</label>
            <div class="relative">
                <input id="password" name="password" :type="lihat ? 'text' : 'password'" type="password" class="input pr-24" required autocomplete="current-password">
                <button type="button" @click="lihat = !lihat" class="absolute inset-y-0 right-3 text-xs font-medium text-blue-600 hover:text-blue-800" x-text="lihat ? 'Sembunyikan' : 'Lihat'">Lihat</button>
            </div>
            @error('password') <p class="galat">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="ingat" value="1" class="size-4 rounded border-slate-300 accent-blue-600"> Tetap masuk di perangkat ini
        </label>
        <button class="btn btn-primary w-full py-2.5">Masuk</button>
    </form>
@endsection
