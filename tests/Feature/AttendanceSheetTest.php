<?php

namespace Tests\Feature;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSheetTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleSession(): ExamSession
    {
        $session = ExamSession::create([
            'session_name' => 'PAT Genap 2026',
            'school_name' => 'MTs Sirojul Athfal Kota Bogor',
            'exam_title' => 'Penilaian Akhir Tahun (PAT) Genap',
            'academic_year' => '2025-2026',
            'issue_city' => 'Bogor',
        ]);

        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Senin, 08 Jun 2026',
            'time_slot' => '07.30 - 09.00',
            'subject_name' => 'Bahasa Indonesia',
            'sort_order' => 1,
        ]);
        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Senin, 08 Jun 2026',
            'time_slot' => '09.30 - 11.00',
            'subject_name' => 'Fiqih',
            'sort_order' => 2,
        ]);

        for ($i = 1; $i <= 20; $i++) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa Uji #' . $i,
                'class_name' => '7A',
                'participant_number' => sprintf('2026-06.7A.%03d', $i),
                'room_name' => 'Ruang 01',
            ]);
        }

        for ($j = 1; $j <= 5; $j++) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa Ruang Dua #' . $j,
                'class_name' => '8A',
                'participant_number' => sprintf('2026-06.8A.%03d', 20 + $j),
                'room_name' => 'Ruang 02',
            ]);
        }

        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Peserta Tanpa Ruang',
            'class_name' => '9A',
            'participant_number' => '2026-06.9A.099',
            'room_name' => null,
        ]);

        return $session;
    }

    public function test_attendance_sheet_ui_renders_preview_and_unassigned_warning(): void
    {
        $session = $this->createSampleSession();

        $response = $this->get(route('exam-sessions.attendance.index', $session));

        $response->assertStatus(200);
        $response->assertSee('Cetak Daftar Hadir Peserta');
        $response->assertSee('DAFTAR HADIR PESERTA');
        $response->assertSee('RUANG 01');
        $response->assertSee('Senin, 08 Jun 2026');
        $response->assertSee('Bahasa Indonesia');
        $response->assertSee('Fiqih');
        $response->assertSee('Peserta Tanpa Ruang');
    }

    public function test_attendance_sheet_persists_signatory_options_in_session(): void
    {
        $session = $this->createSampleSession();

        $response = $this->get(route('exam-sessions.attendance.index', [
            'examSession' => $session->id,
            'chair_name' => 'Nia Suherniawati, S.Sos.I',
            'secretary_name' => 'Jajang Nurjaman, S.Pd.',
            'issue_city' => 'Bogor',
            'issue_year' => '2026',
            'fill_empty_rows' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nia Suherniawati, S.Sos.I');
        $response->assertSee('Jajang Nurjaman, S.Pd.');

        $reloadResponse = $this->get(route('exam-sessions.attendance.index', $session));
        $reloadResponse->assertStatus(200);
        $reloadResponse->assertSee('Nia Suherniawati, S.Sos.I');
        $reloadResponse->assertSee('Jajang Nurjaman, S.Pd.');
    }

    public function test_export_attendance_pdf_all_rooms_and_single_room_returns_valid_pdf(): void
    {
        $session = $this->createSampleSession();

        // 1. Ekspor PDF semua ruang (F4 Landscape)
        $allRoomsPdf = $this->get(route('exam-sessions.attendance.pdf', [
            'examSession' => $session->id,
            'room_filter' => 'all',
            'paper_size' => 'f4_landscape',
            'chair_name' => 'Nia Suherniawati, S.Sos.I',
            'secretary_name' => 'Jajang Nurjaman, S.Pd.',
        ]));

        $allRoomsPdf->assertStatus(200);
        $allRoomsPdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $allRoomsPdf->getContent());

        // 2. Unduh PDF khusus Ruang 02 (A4 Landscape, mode padat tanpa baris kosong)
        $singleRoomDownload = $this->get(route('exam-sessions.attendance.pdf', [
            'examSession' => $session->id,
            'room_filter' => 'Ruang 02',
            'paper_size' => 'a4_landscape',
            'fill_empty_rows' => '0',
            'download' => '1',
        ]));

        $singleRoomDownload->assertStatus(200);
        $singleRoomDownload->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $singleRoomDownload->getContent());
    }
    public function test_session_show_page_displays_attendance_sheet_menu_link(): void
    {
        $session = $this->createSampleSession();

        $response = $this->get(route('exam-sessions.show', $session));

        $response->assertStatus(200);
        $response->assertSee('Daftar Hadir Peserta');
        $response->assertSee(route('exam-sessions.attendance.index', $session));
    }
}