@extends('layouts.portal')

@section('judul', 'Ganti PIN')

@section('isi')
    <form method="POST" action="{{ route('portal.pin.update') }}" class="card mx-auto max-w-md">
        @csrf @method('PUT')
        <div class="space-y-5 p-5 sm:p-6">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Ganti PIN</h1>
                <p class="mt-1 text-sm text-slate-500">PIN terdiri dari 6 angka. Jangan memakai tanggal lahir atau angka berurutan.</p>
            </div>
            <div>
                <label for="pin_lama" class="label">PIN lama</label>
                <input id="pin_lama" type="password" name="pin_lama" inputmode="numeric" class="input" required autocomplete="current-password">
                @error('pin_lama') <p class="galat">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="pin_baru" class="label">PIN baru</label>
                <input id="pin_baru" type="password" name="pin_baru" inputmode="numeric" maxlength="6" class="input" required autocomplete="new-password">
                @error('pin_baru') <p class="galat">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="pin_baru_confirmation" class="label">Ulangi PIN baru</label>
                <input id="pin_baru_confirmation" type="password" name="pin_baru_confirmation" inputmode="numeric" maxlength="6" class="input" required autocomplete="new-password">
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
            <a href="{{ route('portal') }}" class="btn btn-ghost">Batal</a>
            <button class="btn btn-primary">Simpan PIN baru</button>
        </div>
    </form>
@endsection
