@extends('layouts.app')

@section('judul', 'Tagihan Bulanan')
@section('keterangan', 'Tandai anggota yang sudah membayar. Jumlahnya sudah dihitung otomatis.')

@section('aksi')
    @include('partials.pilih-bulan', ['rute' => 'tagihan.index'])
@endsection

@section('isi')
    @php
        $bulan = $periode->format('Y-m');
        $jumlahAnggota = $baris->count();
        $persen = $jumlahAnggota > 0 ? round($jumlahLunas / $jumlahAnggota * 100) : 0;
    @endphp

    {{-- Ringkasan bulan ini --}}
    <div class="card p-5">
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <p class="text-sm text-slate-500">Sudah bayar</p>
                <p class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl text-slate-900 tabular-nums">{{ $jumlahLunas }} <span class="text-sm font-normal text-slate-500">dari {{ $jumlahAnggota }} anggota</span></p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Sudah terkumpul</p>
                <p class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl text-slate-900 tabular-nums">{{ rupiah($terkumpul) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Belum dibayar</p>
                <p class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl text-slate-900 tabular-nums">{{ rupiah($sisaTagihan) }}</p>
            </div>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $persen }}%"></div>
        </div>
    </div>

    <div class="card mt-6" x-data="{
            saring: '{{ $jumlahLunas < $jumlahAnggota ? 'belum' : 'semua' }}', cari: '', buka: null,
            cocok(lunas, nama) {
                const status = this.saring === 'semua' || (this.saring === 'belum' ? !lunas : lunas);
                return status && nama.includes(this.cari.trim().toLowerCase());
            },
        }">
        <div class="card-head">
            <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto">
                <div class="flex w-full gap-1 rounded-xl bg-slate-100 p-1 *:flex-1 *:text-center sm:w-auto sm:*:flex-none">
                    <button type="button" class="tab" :class="saring === 'belum' && 'tab-on'" @click="saring = 'belum'">Belum bayar <span class="text-slate-400">{{ $jumlahAnggota - $jumlahLunas }}</span></button>
                    <button type="button" class="tab" :class="saring === 'sudah' && 'tab-on'" @click="saring = 'sudah'">Sudah bayar <span class="text-slate-400">{{ $jumlahLunas }}</span></button>
                    <button type="button" class="tab" :class="saring === 'semua' && 'tab-on'" @click="saring = 'semua'">Semua</button>
                </div>
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                    <input type="search" x-model="cari" class="input w-full pl-9 sm:w-52" placeholder="Cari nama" aria-label="Cari nama anggota">
                </div>
            </div>

            @if ($belumPotongGaji > 0)
                <form method="POST" action="{{ route('tagihan.bayar-semua') }}" class="w-full sm:w-auto"
                    onsubmit="return confirm('Tandai {{ $belumPotongGaji }} anggota potong gaji sudah bayar untuk {{ $periode->translatedFormat('F Y') }}?')">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <input type="hidden" name="kelompok" value="potong_gaji">
                    <button class="btn btn-primary w-full whitespace-normal sm:w-auto"><x-icon name="check" class="size-4" /> Tandai semua potong gaji ({{ $belumPotongGaji }})</button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="tbl tbl-stack">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="num">Simpanan wajib</th>
                        <th class="num">Manasuka</th>
                        <th class="num">Angsuran</th>
                        <th class="num">Jasa</th>
                        <th class="num">Total</th>
                        <th class="text-right">Status</th>
                    </tr>
                </thead>
                @foreach ($baris as $b)
                    @php
                        $a = $b['anggota'];
                        $nilai = $b['lunas'] ? $b['bayar'] : $b['tagihan'];
                        $metode = $a->potong_gaji ? 'potong_gaji' : 'setor';
                    @endphp
                    <tbody x-show="cocok({{ $b['lunas'] ? 'true' : 'false' }}, @js(mb_strtolower($a->nama)))" x-cloak>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="avatar">{{ $a->inisial }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('anggota.show', $a) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $a->nama }}</a>
                                        <span class="text-xs text-slate-500">{{ $a->kode }} · {{ $a->potong_gaji ? 'Potong gaji' : 'Setor langsung' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="num" data-label="Simpanan wajib">{{ angka($nilai['wajib']) }}</td>
                            <td class="num" data-label="Manasuka">{{ angka($nilai['manasuka']) }}</td>
                            <td class="num" data-label="Angsuran">{{ angka($nilai['angsuran']) }}</td>
                            <td class="num" data-label="Jasa">{{ angka($nilai['jasa']) }}</td>
                            <td class="num font-semibold text-slate-900" data-label="Total">{{ rupiah($nilai['total'], false) }}</td>
                            <td class="max-md:pt-2">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if ($b['lunas'])
                                        <span class="badge badge-green"><x-icon name="check" class="size-3" /> {{ $b['bayar']['tanggal']->translatedFormat('j M') }}</span>
                                        <form method="POST" action="{{ route('tagihan.batal') }}" onsubmit="return confirm(@js('Batalkan pembayaran '.$a->nama.'?'))">
                                            @csrf
                                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                                            <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                            <button class="btn btn-sm btn-danger">Batalkan</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-ghost" @click="buka = buka === {{ $a->id }} ? null : {{ $a->id }}">Ubah jumlah</button>
                                        <form method="POST" action="{{ route('tagihan.bayar') }}">
                                            @csrf
                                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                                            <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                            <input type="hidden" name="metode" value="{{ $metode }}">
                                            <input type="hidden" name="wajib" value="{{ $nilai['wajib'] }}">
                                            <input type="hidden" name="manasuka" value="{{ $nilai['manasuka'] }}">
                                            @foreach ($nilai['pinjaman'] as $id => $p)
                                                <input type="hidden" name="angsuran[{{ $id }}]" value="{{ $p['angsuran'] }}">
                                                <input type="hidden" name="jasa[{{ $id }}]" value="{{ $p['jasa'] }}">
                                            @endforeach
                                            <button class="btn btn-sm btn-soft"><x-icon name="check" class="size-3.5" /> Bayar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @unless ($b['lunas'])
                            {{-- Panel untuk pembayaran yang jumlahnya berbeda dari tagihan --}}
                            <tr x-show="buka === {{ $a->id }}" x-cloak>
                                <td colspan="7" class="bg-blue-50/40">
                                    <form method="POST" action="{{ route('tagihan.bayar') }}" class="py-2">
                                        @csrf
                                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                                        <input type="hidden" name="anggota_id" value="{{ $a->id }}">
                                        <p class="mb-3 text-sm font-medium text-slate-900">Ubah jumlah pembayaran {{ $a->nama }}</p>
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <label class="label">Simpanan wajib</label>
                                                <x-rupiah name="wajib" :value="$nilai['wajib']" />
                                            </div>
                                            <div>
                                                <label class="label">Simpanan manasuka</label>
                                                <x-rupiah name="manasuka" :value="$nilai['manasuka']" />
                                            </div>
                                            @foreach ($nilai['pinjaman'] as $id => $p)
                                                <div>
                                                    <label class="label">Angsuran pinjaman</label>
                                                    <x-rupiah name="angsuran[{{ $id }}]" :value="$p['angsuran']" />
                                                    <p class="hint">Sisa pinjaman {{ rupiah($p['sisa']) }}</p>
                                                </div>
                                                <div>
                                                    <label class="label">Jasa pinjaman</label>
                                                    <x-rupiah name="jasa[{{ $id }}]" :value="$p['jasa']" />
                                                </div>
                                            @endforeach
                                            <div>
                                                <label class="label">Cara bayar</label>
                                                <select name="metode" class="input">
                                                    @foreach (\App\Models\Transaksi::METODE as $kunci => $label)
                                                        <option value="{{ $kunci }}" @selected($kunci === $metode)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <button class="btn btn-primary max-sm:flex-1">Simpan pembayaran</button>
                                            <button type="button" class="btn btn-ghost" @click="buka = null">Tutup</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endunless
                    </tbody>
                @endforeach
            </table>
        </div>

        @if ($baris->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-slate-500">Belum ada anggota aktif.</p>
        @endif
    </div>
@endsection
