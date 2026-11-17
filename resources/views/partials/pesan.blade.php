@if (session('sukses'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" x-data="{ tampil: true }" x-show="tampil">
        <x-icon name="check" class="mt-0.5 size-4" />
        <p class="flex-1">{{ session('sukses') }}</p>
        <button type="button" @click="tampil = false" class="text-emerald-600 hover:text-emerald-800" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
    </div>
@endif

@if (session('galat') || $errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <x-icon name="info" class="mt-0.5 size-4" />
        <div class="flex-1">
            @if (session('galat'))
                <p>{{ session('galat') }}</p>
            @else
                <p class="font-medium">Belum bisa disimpan. Periksa isian berikut:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
