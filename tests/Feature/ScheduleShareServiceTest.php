<?php

namespace Tests\Feature;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use App\Models\Student;
use App\Services\ScheduleShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduleShareServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_reference_table_structure_with_rowspans_and_auto_proctors(): void
    {
        Storage::fake('local');

        $session = ExamSession::create([
            'session_name' => 'SAS Ganjil 2025/2026',
            'school_name' => 'MTSS SIROJUL ATHFAL KOTA BOGOR',
            'exam_title' => 'SUMATIF AKHIR SEMESTER GANJIL',
            'academic_year' => '2025/2026',
        ]);

        // 3 Ruang terdeteksi dari data siswa
        foreach (['Ruang 01', 'Ruang 02', 'Ruang 03'] as $idx => $roomName) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa ' . ($idx + 1),
                'class_name' => '7A',
                'participant_number' => '2025-00' . ($idx + 1),
                'room_name' => $roomName,
            ]);
        }

        $s1 = ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Rabu, 10 Desember 2025',
            'time_slot' => '07.30-09.00',
            'subject_name' => 'Bahasa Indonesia',
            'sort_order' => 1,
        ]);
        $s2 = ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Rabu, 10 Desember 2025',
            'time_slot' => '09.00-10.00',
            'subject_name' => 'Akidah Akhlak',
            'sort_order' => 2,
        ]);

        $service = new ScheduleShareService();
        $service->saveConfig($session, [
            'committees' => [
                '1' => "Tety Rokoyah, S.Pd.I\nInsan Kamil, S.Pd.\nYudi Supriadi, S.Pd.I",
            ],
        ]);
        $service->autoAssignProctors($session, "Hj Ida Hamidah, S.Pd.I\nNani Nurhayati, S.Pd.\nAhmad Mubarok, S.Pd.\nSumi Fatia, S.Pd.I.");

        $result = $service->buildScheduleData($session);

        $this->assertSame(['1', '2', '3'], $result['options']['rooms']);
        $this->assertSame(1, $result['summary']['total_days']);
        $this->assertSame(2, $result['summary']['total_subjects']);
        $this->assertSame(3, $result['summary']['total_rooms']);

        // Hari ke-1 memiliki 2 jam x 3 ruang = rowspan 6
        $dayOne = $result['days'][0];
        $this->assertSame(6, $dayOne['day_rowspan']);
        $this->assertCount(3, $dayOne['committee_lines']);
        $this->assertSame('Tety Rokoyah, S.Pd.I', $dayOne['committee_lines'][0]);

        // Setiap jam memiliki rowspan 3 (sesuai jumlah ruang)
        $this->assertSame(3, $dayOne['slots'][0]['slot_rowspan']);
        $this->assertSame(1, $dayOne['slots'][0]['jam_ke']);
        $this->assertSame(2, $dayOne['slots'][1]['jam_ke']);

        // Pastikan tidak ada pengawas kembar di jam yang sama
        $proctorsJam1 = array_column($dayOne['slots'][0]['room_rows'], 'proctor_name');
        $this->assertCount(3, array_unique($proctorsJam1));

        // Pastikan konfigurasi tersimpan permanen di storage lokal
        $this->assertTrue(Storage::disk('local')->exists('schedule-share/session-' . $session->id . '.json'));
        $this->assertStringContainsString('Bahasa Indonesia', $result['whatsapp_text']);
        $this->assertStringContainsString('Tety Rokoyah, S.Pd.I', $result['whatsapp_text']);
    }

    public function test_defaults_to_six_rooms_when_students_have_no_rooms(): void
    {
        Storage::fake('local');

        $session = ExamSession::create([
            'session_name' => 'Sesi Kosong',
            'school_name' => 'MTs Sirojul Athfal',
        ]);

        $service = new ScheduleShareService();
        $result = $service->buildScheduleData($session);

        $this->assertSame(['1', '2', '3', '4', '5', '6'], $result['options']['rooms']);
        $this->assertSame(0, $result['summary']['total_days']);
    }
    public function test_respects_specific_allowed_days_per_teacher(): void
    {
        Storage::fake('local');

        $session = ExamSession::create([
            'session_name' => 'ASAS 2026',
            'school_name' => 'MTs Sirojul Athfal',
        ]);

        $d1 = ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 1,
            'day_date_label' => 'Rabu, 02 Desember 2026',
            'time_slot' => '07.30-09.00',
            'subject_name' => 'Bahasa Arab',
            'sort_order' => 1,
        ]);
        $d2 = ExamSchedule::create([
            'exam_session_id' => $session->id,
            'day_number' => 2,
            'day_date_label' => 'Kamis, 03 Desember 2026',
            'time_slot' => '07.30-09.00',
            'subject_name' => 'Fikih',
            'sort_order' => 1,
        ]);

        $service = new ScheduleShareService();
        $service->saveConfig($session, ['rooms' => ['1']]);

        // Guru Khusus Hari 2 hanya boleh mengawas di Hari ke-2 (Kamis)
        $rules = [
            ['name' => 'Guru Khusus Hari 1', 'max_days' => 1, 'allowed_days' => [1], 'forbidden_subjects_text' => ''],
            ['name' => 'Guru Khusus Hari 2', 'max_days' => 1, 'allowed_days' => [2], 'forbidden_subjects_text' => ''],
        ];

        $result = $service->autoAssignProctors($session, '', null, $rules);

        $this->assertSame('Guru Khusus Hari 1', $result['proctors'][(string) $d1->id]['1']);
        $this->assertSame('Guru Khusus Hari 2', $result['proctors'][(string) $d2->id]['1']);
    }
}