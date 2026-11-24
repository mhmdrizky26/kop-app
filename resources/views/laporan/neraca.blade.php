@extends('layouts.app')

@section('judul', 'Neraca')
@section('keterangan', 'Tahun buku '.$tahun->nama.' ('.$tahun->mulai->translatedFormat('F Y').' sampai '.$tahun->selesai->translatedFormat('F Y').'), dihitung sampai hari ini. Susunannya mengikuti neraca buku KGB.')

@section('aksi')
    @include('partials.ekspor-laporan', ['rute' => 'laporan.neraca'])
@endsection

@section('isi')
    @php
        $n = $neraca;
        $kolomHarta = ['awal' => 'Saldo awal', 'masuk' => 'Masuk', 'keluar' => 'Keluar', 'akhir' => 'Saldo akhir'];
        $totalKekayaan = collect($n['kekayaan'])->sum(fn ($k) => $k['jumlah'] + $k['laba']);
    @endphp

    {{-- 1. Keadaan harta kekayaan (sumber harta) dan 2. posisi harta --}}
    @foreach ([
        ['judul' => 'Keadaan Harta Kekayaan', 'kepala' => 'Sumber harta', 'baris' => $n['sumber'], 'jumlah' => $n['jumlahSumber'], 'catatan' => 'Dari mana harta koperasi berasal: simpanan anggota dan dana koperasi.'],
        ['judul' => 'Posisi Harta', 'kepala' => 'Posisi harta', 'baris' => $n['posisi'], 'jumlah' => $n['jumlahPosisi'], 'catatan' => 'Di mana harta itu berada selain di kas. Untuk piutang, "masuk" adalah uang yang kembali dan "keluar" adalah uang yang dipinjamkan.'],
    ] as $bagian)
        <div @class(['card', 'mt-6' => ! $loop->first])>
            <div class="card-head">
                <div>
                    <h2 class="card-title">{{ $bagian['judul'] }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $bagian['catatan'] }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl tbl-stack">
                    <thead>
                        <tr>
                            <th class="w-10">No</th>
                            <th>{{ ucfirst($bagian['kepala']) }}</th>
                            @foreach ($kolomHarta as $label)
                                <th class="num">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bagian['baris'] as $nama => $b)
                            <tr>
                                <td class="text-slate-400 max-md:hidden">{{ $loop->iteration }}</td>
                                <td class="font-medium text-slate-900">{{ $nama }}</td>
                                @foreach ($kolomHarta as $kunci => $label)
                                    <td data-label="{{ $label }}" @class(['num', 'font-semibold text-slate-900' => $kunci === 'akhir'])>{{ $kunci === 'akhir' || $kunci === 'awal' ? rupiah($b[$kunci], false) : angka($b[$kunci]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="max-md:hidden"></td>
                            <td>Jumlah</td>
                            @foreach ($kolomHarta as $kunci => $label)
                                <td class="num" data-label="{{ $label }}">{{ rupiah($bagian['jumlah'][$kunci], false) }}</td>
                            @endforeach
                        </tr>
                        @if ($bagian['kepala'] === 'posisi harta')
                            <tr>
                                <td class="max-md:hidden"></td>
                                <td>Jumlah kekayaan di luar kas</td>
                                <td colspan="3" class="max-md:hidden"></td>
                                <td class="num" data-label="Saldo akhir">{{ rupiah($n['jumlahPosisi']['akhir'], false) }}</td>
                            </tr>
                            <tr>
                                <td class="max-md:hidden"></td>
                                <td>Jumlah kekayaan di kas</td>
                                <td colspan="3" class="max-md:hidden"></td>
                                <td class="num" data-label="Saldo akhir">{{ rupiah($n['diKas'], false) }}</td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- 3. Laba satu tahun, biaya, dan SHU bersih --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">Laba 1 Tahun</h2></div>
            <dl class="space-y-2.5 p-5 text-sm">
                @foreach ($n['laba'] as $nama => $nilai)
                    <div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ $loop->iteration }}. {{ $nama }}</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($nilai) }}</dd></div>
                        @if ($nama === 'Laba Usaha Warung KGB' && count($n['rincianUsaha']) > 0)
                            <p class="mt-0.5 pl-4 text-xs text-slate-500">{{ collect($n['rincianUsaha'])->map(fn ($v, $k) => $k.' '.rupiah($v))->join(' · ') }}</p>
                        @endif
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Jumlah</dt><dd class="tabular-nums">{{ rupiah(array_sum($n['laba'])) }}</dd></div>
            </dl>

            <div class="border-t border-slate-100 px-5 pt-4"><h3 class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Pengeluaran biaya</h3></div>
            <dl class="space-y-2.5 p-5 pt-3 text-sm">
                @foreach ($n['biaya'] as $nama => $nilai)
                    <div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ $loop->iteration }}. {{ $nama }}</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($nilai) }}</dd></div>
                        @if ($nama === 'Biaya Usaha' && count($n['rincianBiaya']) > 0)
                            <p class="mt-0.5 pl-4 text-xs text-slate-500">{{ collect($n['rincianBiaya'])->map(fn ($v, $k) => $k.' '.rupiah($v))->join(' · ') }}</p>
                        @endif
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Jumlah</dt><dd class="tabular-nums">{{ rupiah(array_sum($n['biaya'])) }}</dd></div>
            </dl>

            <div class="flex items-center justify-between gap-4 rounded-b-2xl bg-blue-600 px-5 py-4 text-white">
                <span class="font-semibold">SHU bersih</span>
                <span class="text-xl font-semibold tabular-nums">{{ rupiah($n['shuBersih']) }}</span>
            </div>
        </div>

        <div class="space-y-6">
            {{-- 4. Pembagian keuntungan koperasi --}}
            <div class="card">
                <div class="card-head"><h2 class="card-title">Pembagian Keuntungan Koperasi</h2></div>
                <dl class="space-y-2.5 p-5 text-sm">
                    @foreach ($n['pembagian'] as $p)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">{{ $p['nama'] }} <span class="text-slate-400">({{ persen($p['persen']) }})</span></dt>
                            <dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($p['jumlah']) }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between gap-4 border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Jumlah</dt><dd class="tabular-nums">{{ rupiah(collect($n['pembagian'])->sum('jumlah')) }}</dd></div>
                </dl>
            </div>

            {{-- 5. Posisi kekayaan --}}
            <div class="card">
                <div class="card-head"><h2 class="card-title">Posisi Kekayaan KGB</h2></div>
                <div class="overflow-x-auto">
                    <table class="tbl tbl-stack">
                        <thead><tr><th>Posisi</th><th class="num">Jumlah</th><th class="num">Laba usaha</th><th class="num">Total</th></tr></thead>
                        <tbody>
                            @foreach ($n['kekayaan'] as $nama => $k)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $nama }}</td>
                                    <td class="num" data-label="Jumlah">{{ rupiah($k['jumlah'], false) }}</td>
                                    <td class="num" data-label="Laba usaha">{{ angka($k['laba']) }}</td>
                                    <td class="num font-semibold text-slate-900" data-label="Total">{{ rupiah($k['jumlah'] + $k['laba'], false) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Jumlah harta KGB</td>
                                <td class="num" data-label="Jumlah">{{ rupiah(collect($n['kekayaan'])->sum('jumlah'), false) }}</td>
                                <td class="num" data-label="Laba usaha">{{ rupiah(collect($n['kekayaan'])->sum('laba'), false) }}</td>
                                <td class="num" data-label="Total">{{ rupiah($totalKekayaan, false) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
