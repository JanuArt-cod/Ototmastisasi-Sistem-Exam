<?php

namespace App\Console\Commands;

use App\Services\CardLayoutCalculatorService;
use Illuminate\Console\Command;
use RuntimeException;

class TestCardLayoutCommand extends Command
{
    protected $signature = 'cards:test-layout';

    protected $description = 'Menguji kalkulator tata letak kertas (mm) dan rendering komponen kartu tunggal';

    public function handle(CardLayoutCalculatorService $calculator): int
    {
        $this->info('=== 1. Uji Tata Letak Standar A4 Portrait (Kartu 90 x 55 mm) ===');
        $a4Portrait = $calculator->calculate([
            'print_paper_size' => 'A4',
            'print_orientation' => 'portrait',
            'card_width_mm' => 90,
            'card_height_mm' => 55,
            'margin_top_mm' => 10,
            'margin_right_mm' => 10,
            'margin_bottom_mm' => 10,
            'margin_left_mm' => 10,
            'gap_x_mm' => 4,
            'gap_y_mm' => 4,
        ], 5000);

        if (! $a4Portrait['fits_on_page'] or $a4Portrait['cards_per_page'] !== 8 or $a4Portrait['total_pages'] !== 625) {
            throw new RuntimeException('Perhitungan A4 Portrait tidak sesuai harapan.');
        }

        $this->line('Ukuran kertas     : ' . $a4Portrait['page_width_mm'] . ' x ' . $a4Portrait['page_height_mm'] . ' mm');
        $this->line('Area cetak bersih : ' . $a4Portrait['usable_width_mm'] . ' x ' . $a4Portrait['usable_height_mm'] . ' mm');
        $this->line('Susunan per lembar: ' . $a4Portrait['columns'] . ' kolom x ' . $a4Portrait['rows'] . ' baris = ' . $a4Portrait['cards_per_page'] . ' kartu/halaman');
        $this->line('Estimasi 5.000 siswa: ' . $a4Portrait['total_pages'] . ' halaman PDF');
        $this->newLine();

        $this->info('=== 2. Uji Tata Letak A4 Landscape (Margin 8 mm, Kartu 90 x 55 mm) ===');
        $a4Landscape = $calculator->calculate([
            'print_paper_size' => 'A4',
            'print_orientation' => 'landscape',
            'card_width_mm' => 90,
            'card_height_mm' => 55,
            'margin_top_mm' => 8,
            'margin_right_mm' => 8,
            'margin_bottom_mm' => 8,
            'margin_left_mm' => 8,
            'gap_x_mm' => 4,
            'gap_y_mm' => 4,
        ], 100);

        if (! $a4Landscape['fits_on_page'] or $a4Landscape['cards_per_page'] !== 9) {
            throw new RuntimeException('Perhitungan A4 Landscape tidak sesuai harapan.');
        }

        $this->line('Ukuran kertas     : ' . $a4Landscape['page_width_mm'] . ' x ' . $a4Landscape['page_height_mm'] . ' mm');
        $this->line('Susunan per lembar: ' . $a4Landscape['columns'] . ' kolom x ' . $a4Landscape['rows'] . ' baris = ' . $a4Landscape['cards_per_page'] . ' kartu/halaman');
        $this->line('Estimasi 100 siswa: ' . $a4Landscape['total_pages'] . ' halaman PDF');
        $this->newLine();

        $this->info('=== 3. Uji Deteksi Susunan Melebihi Batas Kertas ===');
        $overflow = $calculator->calculate([
            'print_paper_size' => 'A4',
            'print_orientation' => 'portrait',
            'card_width_mm' => 110,
            'card_height_mm' => 70,
            'cards_per_row' => 2,
            'cards_per_column' => 4,
        ], 10);

        if ($overflow['fits_on_page'] or empty($overflow['errors'])) {
            throw new RuntimeException('Deteksi overflow kertas gagal menangkap ukuran kartu yang berlebih.');
        }

        $this->line('Status muat kertas: Tidak muat (terdeteksi aman)');
        foreach ($overflow['errors'] as $err) {
            $this->line('- Peringatan: ' . $err);
        }
        $this->newLine();

        $this->info('=== 4. Uji Render Komponen Kartu Tunggal (Nama Panjang & Nol di Depan) ===');
        $dummySession = (object) [
            'school_name' => 'SMK NEGERI 1 NUSANTARA',
            'school_address' => 'Jl. Pendidikan No. 10, Bogor',
            'card_title' => 'KARTU PESERTA UJIAN',
            'exam_title' => 'PENILAIAN SUMATIF AKHIR SEMESTER',
            'academic_year' => '2026/2027',
            'term' => 'Ganjil',
            'exam_date' => '2026-12-01',
            'principal_name' => 'Drs. H. Ahmad Fauzi, M.Pd.',
            'principal_title' => 'Kepala Sekolah',
            'card_note' => 'Kartu ini wajib dibawa selama ujian berlangsung.',
        ];

        $dummyStudent = (object) [
            'participant_number' => 'UAS-0001',
            'student_name' => 'Muhammad Abdullah Al-Fatih Ramadhan, S.Kom.',
            'nis' => '000123',
            'nisn' => '0098765432',
            'class_name' => 'XII TJKT 1',
            'room_name' => 'Ruang 04',
        ];

        $html = view('cards.partials._single-card', [
            'session' => $dummySession,
            'student' => $dummyStudent,
            'layout' => [
                'card_width_mm' => 90,
                'card_height_mm' => 55,
            ],
            'assets' => [],
        ])->render();

        if (! str_contains($html, '90mm') or ! str_contains($html, '000123') or ! str_contains($html, 'Al-Fatih Ramadhan')) {
            throw new RuntimeException('Hasil render kartu tunggal tidak lengkap.');
        }

        $this->line('Render HTML kartu tunggal berhasil (' . strlen($html) . ' byte).');
        $this->info('Semua pengujian Tahap 5B lulus.');

        return self::SUCCESS;
    }
}