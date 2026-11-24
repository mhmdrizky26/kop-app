<?php

namespace App\Services;

use App\Models\TahunBuku;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengirim dokumen dari DokumenLaporan sebagai halaman cetak, file PDF, atau file Excel (.xlsx),
 * semuanya dengan kop koperasi, judul, periode, tabel, dan kolom tanda tangan.
 */
class EksporLaporan
{
    public const FORMAT = ['cetak', 'pdf', 'xlsx'];

    private const FORMAT_UANG = '#,##0;-#,##0;"-"';

    public static function kirim(array $dokumen, string $format): Response
    {
        return match ($format) {
            'pdf' => Pdf::loadView('laporan.cetak', ['dok' => $dokumen, 'mode' => 'pdf'])
                ->setPaper('a4', $dokumen['orientasi'])
                ->download($dokumen['nama_file'].'.pdf'),
            'xlsx' => self::excel($dokumen),
            default => response()->view('laporan.cetak', ['dok' => $dokumen, 'mode' => 'cetak']),
        };
    }

    /** Isi sel untuk cetak dan PDF. */
    public static function teks(mixed $nilai, string $tipe): string
    {
        return match (true) {
            $nilai === null => '',
            $tipe === 'uang' => angka($nilai),
            $tipe === 'persen' => persen($nilai),
            default => (string) $nilai,
        };
    }

    /** Nama pengesah, atau garis titik-titik jika belum diisi di Pengaturan. */
    public static function pengesah(TahunBuku $tahun, string $kolom): string
    {
        return $tahun->$kolom ?: '(..............................)';
    }

    /** Kota dan tanggal di atas tanda tangan. */
    public static function tempatTanggal(): string
    {
        return trim(config('koperasi.kota').', '.today()->translatedFormat('j F Y'), ', ');
    }

    private static function excel(array $dok): Response
    {
        $buku = new Spreadsheet;
        $s = $buku->getActiveSheet();
        $s->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', '', $dok['judul']), 0, 31));
        $s->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $lebar = max(array_map(fn ($b) => count($b['kolom']), $dok['bagian']));
        $akhir = Coordinate::stringFromColumnIndex($lebar);
        $r = 1;

        $baris = function (string $teks, array $font = [], string $rata = Alignment::HORIZONTAL_CENTER) use ($s, &$r, $akhir): void {
            $s->setCellValue("A{$r}", $teks)->mergeCells("A{$r}:{$akhir}{$r}");
            $s->getStyle("A{$r}")->applyFromArray(['font' => $font, 'alignment' => ['horizontal' => $rata]]);
            $r++;
        };

        // Kop
        $baris(mb_strtoupper(config('koperasi.nama')), ['bold' => true, 'size' => 14]);
        if (config('koperasi.alamat')) {
            $baris(config('koperasi.alamat'));
        }
        $s->getStyle('A'.($r - 1).":{$akhir}".($r - 1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        $r++;
        $baris(mb_strtoupper($dok['judul']), ['bold' => true, 'size' => 12]);
        $baris($dok['periode']);
        $r++;

        $panjang = array_fill(1, $lebar, 6);
        foreach ($dok['bagian'] as $bagian) {
            $kolom = $bagian['kolom'];
            $ujung = Coordinate::stringFromColumnIndex(count($kolom));

            if ($bagian['judul']) {
                $s->setCellValue("A{$r}", mb_strtoupper($bagian['judul']));
                $s->getStyle("A{$r}")->getFont()->setBold(true);
                $r++;
            }

            $mulai = $r;
            foreach ($kolom as $i => [$label]) {
                $s->setCellValue([$i + 1, $r], $label);
            }
            $s->getStyle("A{$r}:{$ujung}{$r}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $r++;

            foreach ([...$bagian['baris'], ...$bagian['jumlah']] as $isi) {
                foreach ($kolom as $i => [, $tipe]) {
                    $nilai = $isi[$i] ?? null;
                    if ($nilai === null) {
                        continue;
                    }
                    $s->setCellValue([$i + 1, $r], $tipe === 'persen' ? persen($nilai) : $nilai);
                    $gaya = $s->getStyle([$i + 1, $r]);
                    match ($tipe) {
                        'uang' => $gaya->getNumberFormat()->setFormatCode(self::FORMAT_UANG),
                        'no' => $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER),
                        'persen' => $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT),
                        default => null,
                    };
                    $tampil = $tipe === 'uang' ? number_format((float) $nilai, 0, ',', '.') : (string) $nilai;
                    $panjang[$i + 1] = max($panjang[$i + 1], mb_strlen($tampil) + 2, mb_strlen($kolom[$i][0]) + 2);
                }
                $r++;
            }

            if ($bagian['jumlah']) {
                $s->getStyle('A'.($r - count($bagian['jumlah'])).":{$ujung}".($r - 1))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
                ]);
            }
            $s->getStyle("A{$mulai}:{$ujung}".($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $r++;
        }

        foreach ($panjang as $i => $karakter) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(min($karakter, 45));
        }

        self::tandaTanganExcel($s, $dok['tahun'], $r + 1, $lebar);

        $s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation($dok['orientasi'] === 'landscape' ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)->setFitToHeight(0);

        return response()->streamDownload(
            fn () => (new Xlsx($buku))->save('php://output'),
            $dok['nama_file'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private static function tandaTanganExcel(Worksheet $s, TahunBuku $tahun, int $r, int $lebar): void
    {
        $tengah = intdiv($lebar, 2);
        $kiri = 'A%d:'.Coordinate::stringFromColumnIndex($tengah).'%d';
        $kanan = Coordinate::stringFromColumnIndex($tengah + 1).'%d:'.Coordinate::stringFromColumnIndex($lebar).'%d';
        $semua = 'A%d:'.Coordinate::stringFromColumnIndex($lebar).'%d';

        $tulis = function (string $rentang, int $baris, string $teks, bool $tebal = false) use ($s): void {
            $sel = sprintf($rentang, $baris, $baris);
            $s->mergeCells($sel)->setCellValue(explode(':', $sel)[0], $teks);
            $s->getStyle($sel)->applyFromArray(['font' => ['bold' => $tebal, 'underline' => $tebal], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        };

        $tulis($kanan, $r, self::tempatTanggal());
        $tulis($kiri, $r + 1, 'Ketua,');
        $tulis($kanan, $r + 1, 'Bendahara,');
        $tulis($kiri, $r + 5, self::pengesah($tahun, 'nama_ketua'), true);
        $tulis($kanan, $r + 5, self::pengesah($tahun, 'nama_bendahara'), true);
        $tulis($semua, $r + 7, 'Mengetahui,');
        $tulis($semua, $r + 8, 'Badan Pemeriksa');
        $tulis($kiri, $r + 12, '1) '.self::pengesah($tahun, 'nama_pemeriksa_1'), true);
        $tulis($kanan, $r + 12, '2) '.self::pengesah($tahun, 'nama_pemeriksa_2'), true);
    }
}
