<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Student;
use App\Services\QrCodeService;
use App\Services\SeatingPdfService;
use App\Services\SeatingRandomizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatingPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithPlan(): array
    {
        $session = ExamSession::create([
            'session_name' => 'ASAS Ganjil 2026/2027',
            'school_name' => 'MTs Sirojul Athfal',
            'exam_title' => 'Asesmen Sumatif Akhir Semester',
            'academic_year' => '2026/2027',
        ]);

        Student::create([
            'exam_session_id' => $session->id,
            'student_name' => 'Ahmad Fauzan Al-Ghazali',
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
            'student_name' => 'Citra Amelia Putri',
            'class_name' => '9A',
            'participant_number' => 'ASAS.0262703',
            'room_name' => 'Ruang 02',
        ]);

        $plan = (new SeatingRandomizerService())->generateAndSavePlan($session);

        return [$session, $plan];
    }

    public function test_export_full_room_pdf_package_returns_valid_pdf_binary(): void
    {
        [$session, $plan] = $this->createSessionWithPlan();

        $response = $this->get(route('exam-sessions.seating.pdf', [
            'examSession' => $session->id,
            'seatingPlan' => $plan->id,
            'document_type' => 'all',
            'room_number' => 0,
            'card_width_mm' => 88,
            'card_height_mm' => 35,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_export_single_room_desk_cards_with_custom_qr_url_persists_per_session(): void
    {
        [$session, $plan] = $this->createSessionWithPlan();

        $pdfService = new SeatingPdfService(new QrCodeService());
        $resolvedUrl = $pdfService->resolveQrTargetUrl(
            'https://denah-ujian.netlify.app/?room={room}',
            $session,
            $plan,
            1,
            'Ruang 01'
        );
        $this->assertSame('https://denah-ujian.netlify.app/?room=1', $resolvedUrl);

        $response = $this->get(route('exam-sessions.seating.pdf', [
            'examSession' => $session->id,
            'seatingPlan' => $plan->id,
            'document_type' => 'desk_cards',
            'room_number' => 1,
            'card_width_mm' => 88,
            'card_height_mm' => 35,
            'qr_custom_url' => 'https://denah-ujian.netlify.app/?room={room}',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        // Pastikan URL tersimpan otomatis di sesi ujian dan terisi kembali di halaman utama seating
        $this->assertSame('https://denah-ujian.netlify.app/?room={room}', $session->fresh()->qr_floor_plan_url);

        $indexResponse = $this->get(route('exam-sessions.seating.index', $session));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Unduh HTML Denah Online');
        $indexResponse->assertSee('https://denah-ujian.netlify.app/?room={room}');
    }

    public function test_save_qr_url_endpoint_updates_and_clears_session_qr_link(): void
    {
        [$session, $plan] = $this->createSessionWithPlan();

        $saveResponse = $this->patch(route('exam-sessions.seating.qr-url', $session), [
            'plan_id' => $plan->id,
            'qr_custom_url' => 'https://denah.sekolah.sch.id/{room_slug}.html',
        ]);

        $saveResponse->assertRedirect(route('exam-sessions.seating.index', [
            'examSession' => $session->id,
            'plan_id' => $plan->id,
        ]));
        $this->assertSame('https://denah.sekolah.sch.id/{room_slug}.html', $session->fresh()->qr_floor_plan_url);

        $clearResponse = $this->patch(route('exam-sessions.seating.qr-url', $session), [
            'plan_id' => $plan->id,
            'qr_custom_url' => '',
        ]);

        $clearResponse->assertRedirect();
        $this->assertNull($session->fresh()->qr_floor_plan_url);
    }

    public function test_cannot_export_pdf_for_plan_belonging_to_another_session(): void
    {
        [$sessionA, $planA] = $this->createSessionWithPlan();
        $sessionB = ExamSession::create([
            'session_name' => 'Sesi Lain',
            'school_name' => 'Sekolah Lain',
            'exam_title' => 'Ujian Lain',
            'academic_year' => '2026/2027',
        ]);

        $response = $this->get(route('exam-sessions.seating.pdf', [
            'examSession' => $sessionB->id,
            'seatingPlan' => $planA->id,
        ]));

        $response->assertStatus(404);
    }
}