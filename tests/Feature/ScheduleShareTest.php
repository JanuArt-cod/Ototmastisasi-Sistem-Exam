<?php

namespace Tests\Feature;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduleShareTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithSchedules(): ExamSession
    {
        $session = ExamSession::create([
            'session_name' => 'SAS Ganjil 2025/2026',
            'school_name' => 'MTSS SIROJUL ATHFAL KOTA BOGOR',
            'exam_title' => 'SUMATIF AKHIR SEMESTER GANJIL',
            'academic_year' => '2025/2026',
        ]);

        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Rabu, 10 Desember 2025',
            'time_slot' => '07.30-09.00',
            'subject_name' => 'Bahasa Indonesia',
            'sort_order' => 1,
        ]);

        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Rabu, 10 Desember 2025',
            'time_slot' => '09.00-10.00',
            'subject_name' => 'Akidah Akhlak',
            'sort_order' => 2,
        ]);

        return $session;
    }

    public function test_save_config_auto_assign_and_export_reference_pdf(): void
    {
        Storage::fake('local');
        $session = $this->createSessionWithSchedules();

        // 1. Pembagian pengawas otomatis
        $autoResp = $this->post(route('exam-sessions.schedules.share.auto-assign', $session), [
            'rooms_text' => '1, 2, 3, 4, 5, 6',
            'teacher_pool_text' => "Hj Ida Hamidah, S.Pd.I\nNani Nurhayati, S.Pd.\nSumi Fatia, S.Pd.I.\nA Suhaemi, S.Ag.M.Pd.I.\nYuyun Suryani, S.Pd.I.\nAhmad Mubarok, S.Pd.",
        ]);
        $autoResp->assertRedirect(route('exam-sessions.schedules.share.index', $session));

        // 2. Simpan header, panitia, dan footer
        $saveResp = $this->post(route('exam-sessions.schedules.share.update', $session), [
            'header_title' => 'JADWAL PENGAWAS RUANG SUMATIF AKHIR SEMESTER GANJIL',
            'footer_signatory' => 'Panitia SAS Tahun 2025/2026',
            'paper_size' => 'f4_portrait',
            'show_proctor_columns' => '1',
            'rooms_text' => '1, 2, 3, 4, 5, 6',
            'committees' => [
                '1' => "Tety Rokoyah, S.Pd.I\nInsan Kamil, S.Pd.\nYudi Supriadi, S.Pd.I",
            ],
        ]);
        $saveResp->assertRedirect(route('exam-sessions.schedules.share.index', $session));

        // 3. Ekspor PDF 8 kolom (mode pengawas & panitia)
        $pdfFull = $this->get(route('exam-sessions.schedules.share.pdf', [
            'examSession' => $session->id,
            'download' => '1',
        ]));
        $pdfFull->assertStatus(200);
        $pdfFull->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdfFull->getContent());

        // 4. Ekspor PDF 5 kolom (mode ringkas siswa/wali murid tanpa pengawas)
        $pdfStudent = $this->get(route('exam-sessions.schedules.share.pdf', [
            'examSession' => $session->id,
            'paper_size' => 'a4_portrait',
            'show_proctor_columns' => '0',
        ]));
        $pdfStudent->assertStatus(200);
        $pdfStudent->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdfStudent->getContent());
    }

    public function test_export_pdf_redirects_with_error_when_schedules_empty(): void
    {
        Storage::fake('local');
        $session = ExamSession::create([
            'session_name' => 'Sesi Tanpa Jadwal',
            'school_name' => 'MTs Sirojul Athfal',
        ]);

        $response = $this->get(route('exam-sessions.schedules.share.pdf', $session));

        $response->assertRedirect(route('exam-sessions.schedules.share.index', $session));
        $response->assertSessionHas('error');
    }

    public function test_share_ui_page_and_navigation_links_render_properly(): void
    {
        Storage::fake('local');
        $session = $this->createSessionWithSchedules();

        $sharePage = $this->get(route('exam-sessions.schedules.share.index', $session));
        $sharePage->assertStatus(200);
        $sharePage->assertSee('Jadwal Ujian &amp; Pengawas Ruang', false);
        $sharePage->assertSee('JADWAL PENGAWAS RUANG SUMATIF AKHIR SEMESTER GANJIL');
        $sharePage->assertSee('Bahasa Indonesia');
        $sharePage->assertSee('Unduh Gambar PNG (WA)');

        $sessionShow = $this->get(route('exam-sessions.show', $session));
        $sessionShow->assertStatus(200);
        $sessionShow->assertSee(route('exam-sessions.schedules.share.index', $session));

        $scheduleIndex = $this->get(route('exam-sessions.schedules.index', $session));
        $scheduleIndex->assertStatus(200);
        $scheduleIndex->assertSee(route('exam-sessions.schedules.share.index', $session));
    }

    public function test_floor_plan_qr_renders_when_configured_and_omits_cleanly_when_empty(): void
    {
        Storage::fake('local');
        $session = $this->createSessionWithSchedules();

        // 1. Tanpa denah duduk / link QR: halaman tetap aman dan blok QR tidak muncul
        $pageWithoutQr = $this->get(route('exam-sessions.schedules.share.index', $session));
        $pageWithoutQr->assertStatus(200);
        $pageWithoutQr->assertDontSee('QR Denah Duduk Digital');

        // 2. Setelah link denah duduk digital diatur: blok QR & SVG data URI tampil pada pratinjau dan PDF
        $session->qr_floor_plan_url = 'https://denah-ujian.example.test/sesi-1';
        $session->save();

        $pageWithQr = $this->get(route('exam-sessions.schedules.share.index', $session));
        $pageWithQr->assertStatus(200);
        $pageWithQr->assertSee('QR Denah Duduk Digital');
        $pageWithQr->assertSee('data:image/svg+xml;base64,', false);

        $pdfWithQr = $this->get(route('exam-sessions.schedules.share.pdf', $session));
        $pdfWithQr->assertStatus(200);
        $pdfWithQr->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdfWithQr->getContent());
    }
}