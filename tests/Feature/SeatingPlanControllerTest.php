<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\SeatingPlan;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithStudents(): ExamSession
    {
        $session = ExamSession::create([
            'session_name' => 'ASAS Ganjil 2026/2027',
            'school_name' => 'MTs Sirojul Athfal',
            'exam_title' => 'Asesmen Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ]);

        $students = [
            ['student_name' => 'Ahmad Fauzan', 'class_name' => '7A', 'participant_number' => 'ASAS.0262701', 'room_name' => 'Ruang 01'],
            ['student_name' => 'Budi Santoso', 'class_name' => '8B', 'participant_number' => 'ASAS.0262702', 'room_name' => 'Ruang 01'],
            ['student_name' => 'Citra Amelia', 'class_name' => '9A', 'participant_number' => 'ASAS.0262703', 'room_name' => 'Ruang 02'],
            ['student_name' => 'Dedi Saputra', 'class_name' => '7B', 'participant_number' => 'ASAS.0262704', 'room_name' => 'Ruang 02'],
        ];

        foreach ($students as $row) {
            Student::create(array_merge($row, ['exam_session_id' => $session->id]));
        }

        return $session;
    }

    public function test_seating_index_page_renders_successfully(): void
    {
        $session = $this->createSessionWithStudents();

        $response = $this->get(route('exam-sessions.seating.index', $session));

        $response->assertStatus(200);
        $response->assertSee('Nomor Meja Peserta');
        $response->assertSee('Ruang 01');
        $response->assertSee('Ruang 02');
    }

    public function test_generate_and_finalize_seating_plan_using_existing_rooms(): void
    {
        $session = $this->createSessionWithStudents();

        $generateResponse = $this->post(route('exam-sessions.seating.generate', $session), [
            'selection_mode' => 'all',
            'starting_seat_number' => 1,
        ]);

        $plan = SeatingPlan::where('exam_session_id', $session->id)->firstOrFail();
        $generateResponse->assertRedirect(route('exam-sessions.seating.index', [
            'examSession' => $session->id,
            'plan_id' => $plan->id,
        ]));

        $this->assertSame(4, $plan->total_students);
        $this->assertSame(2, $plan->total_rooms);

        $finalizeResponse = $this->post(route('exam-sessions.seating.finalize', [$session, $plan]));
        $finalizeResponse->assertRedirect();
        $this->assertSame('finalized', $plan->fresh()->status);

        $indexResponse = $this->get(route('exam-sessions.seating.index', [
            'examSession' => $session->id,
            'filter_room' => 'Ruang 02',
        ]));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Ruang 02');
    }

    public function test_cannot_finalize_seating_plan_belonging_to_another_session(): void
    {
        $sessionA = $this->createSessionWithStudents();
        $sessionB = ExamSession::create([
            'session_name' => 'Sesi Lain',
            'school_name' => 'Sekolah Lain',
            'exam_title' => 'Ujian Lain',
            'academic_year' => '2026/2027',
        ]);

        $this->post(route('exam-sessions.seating.generate', $sessionA), [
            'selection_mode' => 'all',
        ]);
        $planA = SeatingPlan::where('exam_session_id', $sessionA->id)->firstOrFail();

        $response = $this->post(route('exam-sessions.seating.finalize', [$sessionB, $planA]));
        $response->assertStatus(404);
    }
}