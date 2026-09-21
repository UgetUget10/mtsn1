<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Berkas contoh untuk fitur "Import Berita" (App\Filament\Support\PostImporter)
 * — tombol "Unduh Template" di modal import. Kolomnya HARUS tetap sinkron
 * dengan PostImporter::mapNormalizedRow(); kalau kolom baru ditambahkan di
 * sana, tambahkan juga di sini.
 */
class PostImportTemplateController extends Controller
{
    private const HEADER = [
        'title', 'slug', 'excerpt', 'body', 'category', 'tags',
        'status', 'published_at', 'is_featured', 'cover_url',
    ];

    private const SAMPLE_ROWS = [
        [
            'Contoh Judul Berita Pertama',
            '', // dikosongkan → slug dibuat otomatis dari title
            'Ringkasan singkat, tampil di daftar berita dan kartu.',
            '<p>Isi lengkap berita. Boleh memuat tag HTML dasar seperti &lt;p&gt;, &lt;strong&gt;, &lt;a&gt;.</p>',
            'Kegiatan',
            'upacara, kokurikuler',
            'published',
            '2026-01-15 08:00:00',
            '1',
            'https://contoh.com/gambar-sampul.jpg',
        ],
        [
            'Contoh Judul Berita Kedua',
            'contoh-judul-berita-kedua',
            '',
            '<p>Berita ini disimpan sebagai draf karena kolom status diisi "draft".</p>',
            'Pengumuman',
            '',
            'draft',
            '',
            '0',
            '',
        ],
    ];

    public function csv(): Response
    {
        $lines = [$this->csvLine(self::HEADER)];
        foreach (self::SAMPLE_ROWS as $row) {
            $lines[] = $this->csvLine($row);
        }

        // BOM UTF-8 di depan: tanpa ini Excel di Windows sering salah-tebak
        // encoding untuk karakter non-ASCII (mis. tanda kutip pintar, é, dsb).
        $content = "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-berita.csv"',
        ]);
    }

    public function xlsx(): Response
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'post-import-template_').'.xlsx';

        $writer = new XlsxWriter();
        $writer->openToFile($tempPath);
        $writer->addRow(Row::fromValues(self::HEADER));
        foreach (self::SAMPLE_ROWS as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        $content = file_get_contents($tempPath);
        unlink($tempPath);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-import-berita.xlsx"',
        ]);
    }

    /** @param  array<int, string>  $fields */
    private function csvLine(array $fields): string
    {
        return implode(',', array_map(function (string $f) {
            // Kutip field yang mengandung koma, kutip ganda, atau baris baru
            // (RFC 4180) — body berita sering memuat koma di dalam kalimat.
            return preg_match('/[",\r\n]/', $f)
                ? '"'.str_replace('"', '""', $f).'"'
                : $f;
        }, $fields));
    }
}
