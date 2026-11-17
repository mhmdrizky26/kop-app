@extends('layouts.auth')

@section('judul', 'Masuk anggota')

@section('isi')
    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk anggota</h2>
    <p class="mt-1.5 text-sm text-slate-500">Masukkan ID anggota yang diberikan pengurus.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="id_anggota" class="label">ID anggota</label>
            <input id="id_anggota" name="id_anggota" value="{{ old('id_anggota') }}" class="input font-mono tracking-wider uppercase" autofocus required autocomplete="off" autocapitalize="characters" spellcheck="false">
            <p class="hint">Belum punya ID? Minta ke pengurus koperasi.</p>
            @error('id_anggota') <p class="galat">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="ingat" value="1" class="size-4 rounded border-slate-300 accent-blue-600"> Tetap masuk di perangkat ini
        </label>
        <button class="btn btn-primary w-full py-2.5">Masuk</button>
    </form>
@endsection
