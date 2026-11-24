@extends('layouts.app')

@section('judul', 'Tagihan Bulanan')
@section('keterangan', 'Semua setoran anggota dicatat di sini. Boleh dibayar bertahap, sebagian potong gaji dan sebagian setor langsung.')

@section('aksi')
    @include('partials.pilih-bulan', ['rute' => 'tagihan.index'])
@endsection

@section('isi')
    @php
        $bulan = $periode->format('Y-m');
        $jumlahAnggota = $baris->count();
        $persen = $jumlahAnggota > 0 ? round($jumlahLunas / $jumlahAnggota * 100) : 0;
        $posTagihan = ['pokok' => 'Simpanan pokok', 'wajib' => 'Simpanan wajib', 'manasuka' => 'Simpanan manasuka', 'angsuran' => 'Angsuran pinjaman', 'jasa' => 'Jasa pinjaman'];
    @endphp

    {{-- Ringkasan bulan ini --}}
    <div class="card p-5">
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <p class="text-sm text-slate-500">Sudah lunas</p>
                <p class="mt-1 text-xl font-semibold tracking-tight text-slate-900 tabular-nums sm:text-2xl">{{ $jumlahLunas }} <span class="text-sm font-normal text-slate-500">dari {{ $jumlahAnggota }} anggota</span></p>
                @if ($jumlahSebagian > 0)
                    <p class="mt-0.5 text-xs text-amber-700">{{ $jumlahSebagian }} anggota baru bayar sebagian</p>
                @endif
            </div>
            <div>
                <p class="text-sm text-slate-500">Sudah terkumpul</p>
                <p class="mt-1 text-xl font-semibold tracking-tight text-slate-900 tabular-nums sm:text-2xl">{{ rupiah($terkumpul) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Belum dibayar</p>
                <p class="mt-1 text-xl font-semibold tracking-tight text-slate-900 tabular-nums sm:text-2xl">{{ rupiah($sisaTagihan) }}</p>
            </div>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $persen }}%"></div>
        </div>
    </div>

    <div class="mt-6" x-data="{
            saring: '{{ $jumlahLunas < $jumlahAnggota ? 'belum' : 'semua' }}', cari: '', buka: null,
            cocok(lunas, nama) {
                const status = this.saring === 'semua' || (this.saring === 'belum' ? !lunas : lunas);
                return status && nama.includes(this.cari.trim().toLowerCase());
            },
        }">
        {{-- Saring, cari, dan tandai massal --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto">
                <div class="flex w-full gap-1 rounded-xl bg-slate-100 p-1 *:flex-1 *:text-center sm:w-auto sm:*:flex-none">
                    <button type="button" class="tab" :class="saring === 'belum' && 'tab-on'" @click="saring = 'belum'">Belum lunas <span class="text-slate-400">{{ $jumlahAnggota - $jumlahLunas }}</span></button>
                    <button type="button" class="tab" :class="saring === 'sudah' && 'tab-on'" @click="saring = 'sudah'">Lunas <span class="text-slate-400">{{ $jumlahLunas }}</span></button>
                    <button type="button" class="tab" :class="saring === 'semua' && 'tab-on'" @click="saring = 'semua'">Semua</button>
                </div>
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                    <input type="search" x-model="cari" class="input w-full bg-white pl-9 sm:w-52" placeholder="Cari nama" aria-label="Cari nama anggota">
                </div>
            </div>

            @if ($belumPotongGaji > 0 && auth()->user()->can('ubah-data'))
                <form method="POST" action="{{ route('tagihan.bayar-semua') }}" class="w-full sm:w-auto"
                    onsubmit="return confirm('Tandai bagian potong gaji {{ $belumPotongGaji }} anggota sudah dibayar untuk {{ $periode->translatedFormat('F Y') }}? Bagian yang setor langsung tidak ikut ditandai.')">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <input type="hidden" name="kelompok" value="potong_gaji">
                    <button class="btn btn-primary w-full whitespace-normal sm:w-auto"><x-icon name="check" class="size-4" /> Tandai semua potong gaji ({{ $belumPotongGaji }})</button>
                </form>
            @endif
        </div>

        {{-- Satu kartu per anggota --}}
        <div class="mt-4 grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($baris as $b)
                @php
                    $a = $b['anggota'];
                    $tagihan = $b['tagihan'];
                    $bayar = $b['bayar'];
                    $sisa = $b['sisa'];
                    $status = $b['status'];
                    $lunas = $status === 'lunas';

                    // Cara bayar bawaan untuk form: ikut bagian yang masih tersisa jika semuanya sama, selain itu setor langsung.
                    $caraSisa = collect(['pokok', 'wajib', 'manasuka', 'angsuran', 'jasa'])->filter(fn ($pos) => $sisa[$pos] > 0)
                        ->map(fn ($pos) => $a->caraBayar($pos))->unique();
                    $metodeForm = $caraSisa->count() === 1 ? $caraSisa->first() : 'setor';
                @endphp
                <div x-show="cocok({{ $lunas ? 'true' : 'false' }}, @js(mb_strtolower($a->nama)))" x-cloak
                    @class(['card flex flex-col overflow-hidden transition hover:shadow-lg', 'border-amber-300' => $status === 'sebagian'])>
                    {{-- Kepala kartu: anggota dan status --}}
                    <div class="flex items-start justify-between gap-3 p-4 pb-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="avatar">{{ $a->inisial }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('anggota.show', $a) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $a->nama }}</a>
                                <span class="text-xs text-slate-500">{{ $a->kode }} · {{ $a->label_cara_bayar }}</span>
                            </div>
                        </div>
                        @if ($lunas)
                            <span class="badge badge-green shrink-0"><x-icon name="check" class="size-3" /> Lunas</span>
                        @elseif ($status === 'sebagian')
                            <span class="badge badge-amber shrink-0">Sebagian</span>
                        @else
                            <span class="badge badge-slate shrink-0">Belum bayar</span>
                        @endif
                    </div>

                    {{-- Rincian tiap bagian tagihan --}}
                    <dl class="space-y-2 border-y border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                        @foreach ($posTagihan as $pos => $label)
                            @php $nilai = max($tagihan[$pos], $bayar[$pos] ?? 0); @endphp
                            @continue($nilai === 0)
                            @php
                                $rincian = $bayar['rincian'][$pos] ?? null;
                                $posLunas = $sisa[$pos] === 0;
                            @endphp
                            <div class="flex items-start justify-between gap-3">
                                <dt class="min-w-0">
                                    <span class="text-slate-700">{{ $label }}</span>
                                    @if ($posLunas && $rincian)
                                        <span class="flex items-center gap-1 text-xs text-emerald-700"><x-icon name="check" class="size-3" /> {{ collect($rincian['metode'])->map(fn ($m) => \App\Models\Transaksi::METODE[$m] ?? $m)->join(' + ') }} · {{ $rincian['tanggal']->translatedFormat('j M') }}</span>
                                    @else
                                        <span class="block text-xs text-slate-400">Belum · {{ \App\Models\Transaksi::METODE[$a->caraBayar($pos)] ?? 'Setor langsung' }}</span>
                                    @endif
                                </dt>
                                <dd @class(['shrink-0 font-medium tabular-nums', 'text-slate-900' => ! $posLunas, 'text-emerald-700' => $posLunas && $rincian])>{{ rupiah($nilai, false) }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Total dan tombol --}}
                    <div class="mt-auto p-4">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm text-slate-500">{{ $lunas ? 'Total dibayar' : 'Belum dibayar' }}</span>
                            <span class="text-lg font-semibold text-slate-900 tabular-nums">{{ rupiah($lunas ? $bayar['total'] ?? 0 : $sisa['total']) }}</span>
                        </div>
                        @if ($status === 'sebagian')
                            <p class="text-right text-xs text-slate-500">dari total {{ rupiah(max($tagihan['total'], $bayar['total'])) }}</p>
                        @endif

                        @can('ubah-data')
                        <div class="mt-3 flex flex-wrap gap-2">
                            @unless ($lunas)
                                <form method="POST" action="{{ route('tagihan.bayar') }}" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                    <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                    <input type="hidden" name="otomatis" value="1">
                                    <button class="btn btn-sm btn-primary w-full"><x-icon name="check" class="size-3.5" /> {{ $bayar ? 'Bayar sisa' : 'Bayar semua' }}</button>
                                </form>
                            @endunless
                            <button type="button" @class(['btn btn-sm btn-soft', 'flex-1' => $lunas]) @click="buka = buka === {{ $a->id }} ? null : {{ $a->id }}">{{ $lunas ? 'Setor tambahan' : 'Bayar sebagian' }}</button>
                            @if ($bayar)
                                <form method="POST" action="{{ route('tagihan.batal') }}" onsubmit="return confirm(@js('Batalkan semua pembayaran '.$a->nama.' bulan ini?'))">
                                    @csrf
                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                    <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                    <button class="btn btn-sm btn-ghost text-red-600 hover:text-red-700">Batalkan</button>
                                </form>
                            @endif
                        </div>
                        @endcan
                    </div>

                    {{-- Form pembayaran sebagian, jumlah berbeda, atau setoran tambahan --}}
                    @can('ubah-data')
                    <form method="POST" action="{{ route('tagihan.bayar') }}" x-show="buka === {{ $a->id }}" x-cloak class="border-t border-slate-100 bg-blue-50/40 p-4">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                        <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                        <p class="text-sm font-medium text-slate-900">{{ $lunas ? 'Setoran tambahan' : 'Bayar sebagian' }}</p>
                        <p class="mb-3 text-xs text-slate-500">
                            {{ $lunas
                                ? 'Tagihan sudah lunas. Yang masih bisa ditambah: simpanan manasuka dan angsuran lebih awal.'
                                : 'Terisi sisa yang belum dibayar. Isi 0 untuk bagian yang dibayar nanti.' }}
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @if ($sisa['pokok'] > 0)
                                <div>
                                    <label class="label">Simpanan pokok</label>
                                    <x-rupiah name="pokok" :value="$sisa['pokok']" />
                                </div>
                            @endif
                            @if ($sisa['wajib'] > 0)
                                <div>
                                    <label class="label">Simpanan wajib</label>
                                    <x-rupiah name="wajib" :value="$sisa['wajib']" />
                                </div>
                            @endif
                            <div>
                                <label class="label">Simpanan manasuka</label>
                                <x-rupiah name="manasuka" :value="$sisa['manasuka']" />
                            </div>
                            @foreach ($sisa['pinjaman'] as $id => $p)
                                <div>
                                    <label class="label">Angsuran pinjaman</label>
                                    <x-rupiah name="angsuran[{{ $id }}]" :value="$p['angsuran']" />
                                    <p class="hint">Sisa pinjaman {{ rupiah($p['sisa']) }}</p>
                                </div>
                                @if ($p['jasa'] > 0)
                                    <div>
                                        <label class="label">Jasa pinjaman</label>
                                        <x-rupiah name="jasa[{{ $id }}]" :value="$p['jasa']" />
                                    </div>
                                @endif
                            @endforeach
                            <div>
                                <label class="label">Cara bayar</label>
                                <select name="metode" class="input">
                                    @foreach (\App\Models\Transaksi::METODE as $kunci => $label)
                                        <option value="{{ $kunci }}" @selected($kunci === $metodeForm)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button class="btn btn-sm btn-primary flex-1">Simpan pembayaran</button>
                            <button type="button" class="btn btn-sm btn-ghost" @click="buka = null">Tutup</button>
                        </div>
                    </form>
                    @endcan
                </div>
            @endforeach
        </div>

        @if ($baris->isEmpty())
            <p class="card mt-4 px-5 py-12 text-center text-sm text-slate-500">Belum ada anggota aktif.</p>
        @endif
    </div>
@endsection
