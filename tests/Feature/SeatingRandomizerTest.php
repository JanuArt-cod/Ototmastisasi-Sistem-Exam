<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Student;
use App\Services\SeatingRandomizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeatingRandomizerTest extends TestCase
{
    use RefreshDatabase;

    private SeatingRandomizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SeatingRandomizerService();
    }

    private function createSession(string $name = 'Sesi Uji Nomor Meja'): ExamSession
    {
        return ExamSession::create([
            'session_name' => $name,
            'school_name' => 'MTs Contoh Nusantara',
            'exam_title' => 'Asesmen Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ]);
    }

    private function seedSyntheticStudents(ExamSession $session, array $rows): void
    {
        foreach ($rows as $row) {
            Student::create([
                'exam_session_id' => $session->id,
                'student_name' => $row['name'],
                'class_name' => $row['class'],
                'participant_number' => $row['number'],
                'room_name' => array_key_exists('room', $row) ? $row['room'] : 'Ruang 01',
            ]);
        }
    }

    public function test_two_students_from_different_grades_pair_on_desk_one(): void
    {
        $session = $this->createSession();
        $this->seedSyntheticStudents($session, [
            ['name' => 'Siswa Satu', 'class' => '7A', 'number' => 'ASAS.0262701', 'room' => 'Ruang 01'],
            ['name' => 'Siswa Dua', 'class' => '8A', 'number' => 'ASAS.0262702', 'room' => 'Ruang 01'],
        ]);

        $plan = $this->service->generateAndSavePlan($session);

        $this->assertSame(2, $plan->total_students);
        $this->assertSame(1, $plan->total_desks);
        $this->assertSame(1, $plan->total_rooms);
        $this->assertSame(1, $plan->cross_grade_pairs);

        $assignments = $plan->assignments;
        $this->assertSame('Ruang 01', $assignments[0]->room_name);
        $this->assertSame(1, $assignments[0]->seat_number);
        $this->assertSame($assignments[0]->participant_number_snapshot . '.1', $assignments[0]->desk_participant_number);

        $this->assertSame('Ruang 01', $assignments[1]->room_name);
        $this->assertSame(2, $assignments[1]->seat_number);
        $this->assertSame($assignments[1]->participant_number_snapshot . '.2', $assignments[1]->desk_participant_number);
    }

    public function test_existing_rooms_reset_seat_and_desk_numbers_per_room(): void
    {
        $session = $this->createSession('Sesi 65 Siswa 3 Ruangan');
        $grades = ['7A', '8A', '9A'];
        $rows = [];
        for ($i = 1; $i <= 65; $i++) {
            $room = $i <= 30 ? 'Ruang 01' : ($i <= 60 ? 'Ruang 02' : 'Ruang 03');
            $rows[] = [
                'name' => 'Peserta ' . $i,
                'class' => $grades[$i % 3],
                'number' => 'ASAS.' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'room' => $room,
            ];
        }
        $this->seedSyntheticStudents($session, $rows);

        $plan = $this->service->generateAndSavePlan($session);

        $this->assertSame(65, $plan->total_students);
        $this->assertSame(3, $plan->total_rooms);

        $ruang1 = $plan->assignments->where('room_name', 'Ruang 01')->values();
        $ruang2 = $plan->assignments->where('room_name', 'Ruang 02')->values();
        $ruang3 = $plan->assignments->where('room_name', 'Ruang 03')->values();

        $this->assertCount(30, $ruang1);
        $this->assertSame(1, $ruang1->first()->seat_number);
        $this->assertSame(30, $ruang1->last()->seat_number);
        $this->assertSame(15, $ruang1->last()->desk_number);

        $this->assertCount(30, $ruang2);
        $this->assertSame(1, $ruang2->first()->seat_number);
        $this->assertSame(30, $ruang2->last()->seat_number);
        $this->assertSame(15, $ruang2->last()->desk_number);

        $this->assertCount(5, $ruang3);
        $this->assertSame(1, $ruang3->first()->seat_number);
        $this->assertSame(5, $ruang3->last()->seat_number);
    }

    public function test_missing_room_assignment_blocks_generation_with_clear_message(): void
    {
        $session = $this->createSession();
        $this->seedSyntheticStudents($session, [
            ['name' => 'Tanpa Ruang', 'class' => '7A', 'number' => 'P-01', 'room' => null],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('belum memiliki ruangan ujian');
        $this->service->generateAndSavePlan($session);
    }

    public function test_odd_number_of_students_leaves_single_student_on_last_desk(): void
    {
        $session = $this->createSession();
        $this->seedSyntheticStudents($session, [
            ['name' => 'S1', 'class' => 'Kelas 7', 'number' => 'P-01', 'room' => 'Ruang 01'],
            ['name' => 'S2', 'class' => 'Kelas 8', 'number' => 'P-02', 'room' => 'Ruang 01'],
            ['name' => 'S3', 'class' => 'Kelas 9', 'number' => 'P-03', 'room' => 'Ruang 01'],
            ['name' => 'S4', 'class' => 'Kelas 7', 'number' => 'P-04', 'room' => 'Ruang 01'],
            ['name' => 'S5', 'class' => 'Kelas 8', 'number' => 'P-05', 'room' => 'Ruang 01'],
        ]);

        $plan = $this->service->generateAndSavePlan($session);

        $this->assertSame(5, $plan->total_students);
        $this->assertSame(3, $plan->total_desks);
        $this->assertSame(1, $plan->single_seat_desks);

        $lastAssignment = $plan->assignments->last();
        $this->assertSame(5, $lastAssignment->seat_number);
        $this->assertSame(3, $lastAssignment->desk_number);
        $this->assertSame(1, $lastAssignment->seat_position);
    }

    public function test_duplicate_participant_numbers_block_generation(): void
    {
        $session = $this->createSession();
        $this->seedSyntheticStudents($session, [
            ['name' => 'S1', 'class' => '7A', 'number' => 'DUP-01', 'room' => 'Ruang 01'],
            ['name' => 'S2', 'class' => '8A', 'number' => 'DUP-01', 'room' => 'Ruang 01'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->generateAndSavePlan($session);
    }

    public function test_rerandomize_creates_new_version_and_finalize_locks_status(): void
    {
        $session = $this->createSession();
        $this->seedSyntheticStudents($session, [
            ['name' => 'S1', 'class' => '7A', 'number' => 'V-01', 'room' => 'Ruang 01'],
            ['name' => 'S2', 'class' => '8A', 'number' => 'V-02', 'room' => 'Ruang 01'],
        ]);

        $v1 = $this->service->generateAndSavePlan($session);
        $this->service->finalizePlan($v1);

        $v2 = $this->service->generateAndSavePlan($session);

        $this->assertSame(1, $v1->fresh()->version_number);
        $this->assertSame('finalized', $v1->fresh()->status);
        $this->assertSame(2, $v2->version_number);
        $this->assertSame('draft', $v2->status);
    }

    public function test_thousand_students_scale_and_integrity(): void
    {
        $session = $this->createSession('Sesi 1000 Siswa');
        $grades = ['7A', '7B', '8A', '8B', '9A', '9B'];
        $bulk = [];
        $now = now();

        for ($i = 1; $i <= 1000; $i++) {
            $roomIndex = (int) ceil($i / 30);
            $bulk[] = [
                'exam_session_id' => $session->id,
                'student_name' => 'Siswa Sintetis ' . $i,
                'class_name' => $grades[$i % count($grades)],
                'participant_number' => 'ASAS.' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'room_name' => 'Ruang ' . str_pad((string) $roomIndex, 2, '0', STR_PAD_LEFT),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Student::insert($bulk);

        $plan = $this->service->generateAndSavePlan($session);

        $this->assertSame(1000, $plan->total_students);
        $this->assertSame(34, $plan->total_rooms);
        $this->assertSame(1000, $plan->assignments()->count());
        $this->assertSame(1000, $plan->assignments()->distinct('student_id')->count('student_id'));
    }
}