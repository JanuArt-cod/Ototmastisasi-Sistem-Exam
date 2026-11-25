<?php

namespace Tests\Unit;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use App\Models\Student;
use App\Services\AttendanceSheetService;
use App\Services\SeatingRandomizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSheetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithSchedules(): ExamSession
    {
        $session = ExamSession::create([
            'session_name' => 'PAT Genap 2026',
            'school_name' => 'MTs Sirojul Athfal Kota Bogor',
            'exam_title' => 'Penilaian Akhir Tahun (PAT) Genap',
            'academic_year' => '2025-2026',
            'issue_city' => 'Bogor',
        ]);

        // Hari 1: 3 mata pelajaran
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
        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Senin, 08 Jun 2026',
            'time_slot' => '11.15 - 12.30',
            'subject_name' => 'SKI',
            'sort_order' => 3,
        ]);

        // Hari 2: 2 mata pelajaran
        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 2,
            'day_date_label' => 'Selasa, 09 Jun 2026',
            'time_slot' => '07.30 - 09.00',
            'subject_name' => 'Bahasa Inggris',
            'sort_order' => 1,
        ]);
        ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 2,
            'day_date_label' => 'Selasa, 09 Jun 2026',
            'time_slot' => '09.30 - 11.00',
            'subject_name' => 'Matematika',
            'sort_order' => 2,
        ]);

        return $session;
    }

    public function test_groups_schedules_by_day_and_paginates_34_capacity_per_room(): void
    {
        $session = $this->createSessionWithSchedules();

        // Buat 32 siswa di Ruang 01 (mirip halaman 1-2 absen peserta.pdf)
        for ($i = 1; $i <= 32; $i++) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa Ruang Satu #' . $i,
                'class_name' => $i % 2 === 0 ? '8A' : '7A',
                'participant_number' => sprintf('2026-06.7A.%03d', $i),
                'room_name' => 'Ruang 01',
            ]);
        }

        // Buat 10 siswa di Ruang 02 dan 1 siswa tanpa ruang
        for ($j = 1; $j <= 10; $j++) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa Ruang Dua #' . $j,
                'class_name' => '7B',
                'participant_number' => sprintf('2026-06.7B.%03d', 32 + $j),
                'room_name' => 'Ruang 02',
            ]);
        }

        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Siswa Belum Dapat Ruang',
            'class_name' => '9A',
            'participant_number' => '2026-06.9A.099',
            'room_name' => null,
        ]);

        $service = new AttendanceSheetService();
        $result = $service->buildAttendanceData($session);

        // Verifikasi ringkasan & deteksi siswa tanpa ruang
        $this->assertSame(43, $result['summary']['total_students']);
        $this->assertSame(42, $result['summary']['assigned_students']);
        $this->assertSame(1, $result['summary']['unassigned_students']);
        $this->assertSame('Siswa Belum Dapat Ruang', $result['unassigned_students'][0]['student_name']);

        // Verifikasi pengelompokan jadwal bertingkat
        $this->assertSame(2, $result['summary']['total_schedule_days']);
        $this->assertSame(5, $result['summary']['total_subjects']);
        $this->assertSame(3, $result['schedule_groups'][0]['colspan']);
        $this->assertSame(2, $result['schedule_groups'][1]['colspan']);

        // Verifikasi Ruang 01: 32 siswa + 2 baris kosong = 34 slot (Hal 1: 1-18, Hal 2: 19-34)
        $room1 = $result['rooms'][0];
        $this->assertSame('Ruang 01', $room1['room_name']);
        $this->assertSame(32, $room1['student_count']);
        $this->assertSame(2, $room1['page_count']);

        $this->assertSame(1, $room1['pages'][0]['start_row']);
        $this->assertSame(18, $room1['pages'][0]['end_row']);
        $this->assertFalse($room1['pages'][0]['is_last_page']);

        $this->assertSame(19, $room1['pages'][1]['start_row']);
        $this->assertSame(34, $room1['pages'][1]['end_row']);
        $this->assertTrue($room1['pages'][1]['is_last_page']);
        $this->assertTrue($room1['pages'][1]['rows'][15]['is_empty']);

        // Verifikasi Ruang 02: nomor urut reset mulai dari 1 dan nomor peserta asli dipertahankan
        $room2 = $result['rooms'][1];
        $this->assertSame('Ruang 02', $room2['room_name']);
        $this->assertSame(1, $room2['pages'][0]['rows'][0]['row_number']);
        $this->assertSame('2026-06.7B.033', $room2['pages'][0]['rows'][0]['participant_number']);
    }

    public function test_supports_more_than_34_students_compact_mode_and_finalized_seat_sorting(): void
    {
        $session = $this->createSessionWithSchedules();

        // 40 siswa di Ruang 01 (> 34 kapasitas default)
        for ($i = 1; $i <= 40; $i++) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Peserta Padat #' . $i,
                'class_name' => $i % 2 === 0 ? '8B' : '7A',
                'participant_number' => sprintf('P-%03d', $i),
                'room_name' => 'Ruang 01',
            ]);
        }

        // Buat rencana duduk yang difinalisasi
        $plan = (new SeatingRandomizerService())->generateAndSavePlan($session);
        $plan->update(['status' => 'finalized']);

        $service = new AttendanceSheetService();
        $result = $service->buildAttendanceData($session, [
            'fill_empty_rows' => false,
            'sort_by' => 'seating_seat',
        ]);

        $room1 = $result['rooms'][0];
        // 40 siswa -> Hal 1 (1-18), Hal 2 (19-34), Hal 3 (35-40)
        $this->assertSame(3, $room1['page_count']);
        $this->assertSame(1, $room1['pages'][0]['start_row']);
        $this->assertSame(18, $room1['pages'][0]['end_row']);
        $this->assertSame(19, $room1['pages'][1]['start_row']);
        $this->assertSame(34, $room1['pages'][1]['end_row']);
        $this->assertSame(35, $room1['pages'][2]['start_row']);
        $this->assertSame(40, $room1['pages'][2]['end_row']);
        $this->assertTrue($room1['pages'][2]['is_last_page']);

        // Pastikan urutan mengikuti seat_number #1 pada SeatingAssignment, namun tetap memakai nomor peserta asli
        $firstSeatAssignment = $plan->assignments()->where('seat_number', 1)->first();
        $this->assertSame($firstSeatAssignment->participant_number_snapshot, $room1['pages'][0]['rows'][0]['participant_number']);
    }
}