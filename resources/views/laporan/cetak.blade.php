{{-- Laporan formal untuk cetak dan PDF. Hanya CSS sederhana (tabel, tanpa flex/grid) supaya dompdf bisa merendernya. --}}
@php
    use App\Services\EksporLaporan;
    $tahun = $dok['tahun'];
    $lebarKertas = $dok['orientasi'] === 'landscape' ? '297mm' : '210mm';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $dok['judul'] }} · {{ config('koperasi.nama') }}</title>
    <style>
        @page { size: A4 {{ $dok['orientasi'] }}; margin: 16mm 14mm 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: {{ $dok['orientasi'] === 'landscape' ? '8pt' : '8.5pt' }}; color: #000; margin: 0; }
        .kertas { max-width: {{ $lebarKertas }}; margin: 0 auto; }
        .kop { width: 100%; border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 14px; text-align: center; }
        .kop .nama { font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .kop .alamat { font-size: 9pt; margin-top: 2px; }
        .judul { text-align: center; font-size: 11.5pt; font-weight: bold; text-transform: uppercase; text-decoration: underline; margin: 0; }
        .periode { text-align: center; margin: 3px 0 14px; }
        h2 { font-size: 9pt; font-weight: bold; text-transform: uppercase; margin: 14px 0 5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 0.6pt solid #000; padding: 3pt 5pt; vertical-align: top; }
        table.data thead th { background: #d9d9d9; text-align: center; vertical-align: middle; font-weight: bold; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data tfoot td, table.data tr.jumlah td { background: #f2f2f2; font-weight: bold; }
        /* Kolom angka selebar isinya; sisa lebar untuk kolom teks seperti nama anggota. */
        .no { text-align: center; width: 1%; white-space: nowrap; }
        .kode { width: 1%; white-space: nowrap; }
        .uang, .persen { text-align: right; white-space: nowrap; width: 1%; }
        .ttd { width: 100%; margin-top: 26px; page-break-inside: avoid; border-collapse: collapse; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 6pt; }
        .ttd .ruang { height: 52pt; }
        .ttd .nama { font-weight: bold; text-decoration: underline; }
        .dicetak { margin-top: 18px; font-size: 7pt; color: #555; }
        .kaki { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #555; }
        .kaki .halaman:after { content: "Halaman " counter(page); }
        .utuh { page-break-inside: avoid; }
        .alat { position: sticky; top: 0; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; padding: 8px; text-align: center; font-family: Arial, sans-serif; }
        .alat button { font: inherit; font-size: 10pt; padding: 6px 14px; margin: 0 4px; border: 1px solid #94a3b8; border-radius: 6px; background: #fff; cursor: pointer; }
        .alat button.utama { background: #2563eb; border-color: #2563eb; color: #fff; }
        @if ($mode === 'cetak')
            {{-- Hanya untuk pratinjau di browser; dompdf memakai media "screen", jadi aturan ini tidak dikirim ke PDF. --}}
            @media screen { body { background: #e2e8f0; } .kertas { background: #fff; padding: 16mm 14mm; margin: 16px auto; box-shadow: 0 1px 6px rgba(0,0,0,.15); } }
            @media print { .alat { display: none; } }
        @endif
    </style>
</head>
<body>
    @if ($mode === 'cetak')
        <div class="alat">
            <button type="button" class="utama" onclick="window.print()">Cetak</button>
            <button type="button" onclick="window.close()">Tutup</button>
        </div>
    @else
        <div class="kaki">
            <table style="width: 100%"><tr>
                <td>{{ config('koperasi.nama') }} · {{ $dok['judul'] }}</td>
                <td style="text-align: right"><span class="halaman"></span></td>
            </tr></table>
        </div>
    @endif

    <div class="kertas">
        <div class="kop">
            <div class="nama">{{ config('koperasi.nama') }}</div>
            @if (config('koperasi.alamat'))
                <div class="alamat">{{ config('koperasi.alamat') }}</div>
            @endif
        </div>

        <p class="judul">{{ $dok['judul'] }}</p>
        <p class="periode">{{ $dok['periode'] }}</p>

        @foreach ($dok['bagian'] as $bagian)
            {{-- Tabel pendek tidak dipotong ke halaman berikutnya; tabel panjang (daftar anggota) boleh berlanjut. --}}
            <div @class(['utuh' => count($bagian['baris']) <= 15])>
            @if ($bagian['judul'])
                <h2>{{ $bagian['judul'] }}</h2>
            @endif
            <table class="data">
                <thead>
                    <tr>
                        @foreach ($bagian['kolom'] as [$label, $tipe])
                            <th @class(['no' => $tipe === 'no'])>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bagian['baris'] as $isi)
                        <tr>
                            @foreach ($bagian['kolom'] as $i => [, $tipe])
                                <td class="{{ $tipe }}">{{ EksporLaporan::teks($isi[$i] ?? null, $tipe) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    {{-- Baris jumlah ditaruh di tbody supaya dompdf tidak mengulangnya di setiap halaman --}}
                    @foreach ($bagian['jumlah'] as $isi)
                        <tr class="jumlah">
                            @foreach ($bagian['kolom'] as $i => [, $tipe])
                                <td class="{{ $tipe }}">{{ EksporLaporan::teks($isi[$i] ?? null, $tipe) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endforeach

        <table class="ttd">
            <tr><td></td><td>{{ EksporLaporan::tempatTanggal() }}</td></tr>
            <tr><td>Ketua,</td><td>Bendahara,</td></tr>
            <tr><td class="ruang"></td><td class="ruang"></td></tr>
            <tr>
                <td class="nama">{{ EksporLaporan::pengesah($tahun, 'nama_ketua') }}</td>
                <td class="nama">{{ EksporLaporan::pengesah($tahun, 'nama_bendahara') }}</td>
            </tr>
            <tr><td colspan="2" style="padding-top: 16pt">Mengetahui,<br>Badan Pemeriksa</td></tr>
            <tr><td class="ruang"></td><td class="ruang"></td></tr>
            <tr>
                <td class="nama">1) {{ EksporLaporan::pengesah($tahun, 'nama_pemeriksa_1') }}</td>
                <td class="nama">2) {{ EksporLaporan::pengesah($tahun, 'nama_pemeriksa_2') }}</td>
            </tr>
        </table>

        <p class="dicetak">Dicetak pada {{ now()->translatedFormat('j F Y, H.i') }}</p>
    </div>

    @if ($mode === 'cetak')
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
