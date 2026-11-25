<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Student;
use App\Services\SeatingRandomizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatingFloorPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_floor_plan_and_local_qr_code_render_per_room(): void
    {
        $session = ExamSession::create([
            'session_name' => 'ASAS Ganjil 2026/2027',
            'school_name' => 'MTs Sirojul Athfal',
            'exam_title' => 'Asesmen Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ]);

        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Ahmad Fauzan',
            'class_name' => '7A',
            'participant_number' => 'ASAS.0262701',
            'room_name' => 'Ruang 01',
        ]);
        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Budi Santoso',
            'class_name' => '8B',
            'participant_number' => 'ASAS.0262702',
            'room_name' => 'Ruang 01',
        ]);
        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Citra Amelia',
            'class_name' => '9A',
            'participant_number' => 'ASAS.0262703',
            'room_name' => 'Ruang 02',
        ]);

        $plan = (new SeatingRandomizerService())->generateAndSavePlan($session);

        $responseRoom1 = $this->get(route('exam-sessions.seating.floor-plan', [
            'examSession' => $session->id,
            'seatingPlan' => $plan->id,
            'room' => 1,
        ]));

        $responseRoom1->assertStatus(200);
        $responseRoom1->assertSee('DENAH DUDUK DIGITAL');
        $responseRoom1->assertSee('Ruang 01');
        $responseRoom1->assertSee('MEJA 1');
        $responseRoom1->assertSee('Ahmad Fauzan');
        $responseRoom1->assertSee('data:image/svg+xml;base64,');

        $responseRoom2 = $this->get(route('exam-sessions.seating.floor-plan', [
            'examSession' => $session->id,
            'seatingPlan' => $plan->id,
            'room' => 2,
        ]));

        $responseRoom2->assertStatus(200);
        $responseRoom2->assertSee('Ruang 02');
        $responseRoom2->assertSee('Citra Amelia');
    }

    public function test_export_standalone_static_html_floor_plan_for_online_hosting(): void
    {
        $session = ExamSession::create([
            'session_name' => 'ASAS Ganjil 2026/2027',
            'school_name' => 'MTs Sirojul Athfal',
            'exam_title' => 'Asesmen Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ]);

        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Ahmad Fauzan',
            'class_name' => '7A',
            'participant_number' => 'ASAS.0262701',
            'room_name' => 'Ruang 01',
        ]);
        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Citra Amelia',
            'class_name' => '9A',
            'participant_number' => 'ASAS.0262702',
            'room_name' => 'Ruang 02',
        ]);

        $plan = (new SeatingRandomizerService())->generateAndSavePlan($session);

        $response = $this->get(route('exam-sessions.seating.floor-plan.export-html', [
            'examSession' => $session->id,
            'seatingPlan' => $plan->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        $response->assertSee('DENAH DUDUK UJIAN ONLINE', false);
        $response->assertSee('Ruang 01', false);
        $response->assertSee('Ruang 02', false);
        $response->assertSee('Ahmad Fauzan', false);
        $response->assertSee('Citra Amelia', false);
        $response->assertDontSee('http://127.0.0.1', false);
    }
}