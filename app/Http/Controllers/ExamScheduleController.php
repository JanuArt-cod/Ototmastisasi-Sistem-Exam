<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\ExamSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamScheduleController extends Controller
{
    public function index(ExamSession $examSession): View
    {
        $schedules = $examSession->schedules()->get();

        $groupedDays = $schedules->groupBy(function (ExamSchedule $item) {
            return $item->day_number . '||' . trim($item->day_date_label);
        });

        $dayMap = [];
        foreach ($schedules as $item) {
            if (! isset($dayMap[$item->day_number])) {
                $dayMap[$item->day_number] = $item->day_date_label;
            }
        }

        $defaultDayNumber = $schedules->isNotEmpty()
            ? (int) $schedules->max('day_number')
            : 1;
        $defaultDayLabel = $dayMap[$defaultDayNumber] ?? '';

        return view('schedules.index', [
            'session' => $examSession,
            'schedules' => $schedules,
            'groupedDays' => $groupedDays,
            'dayMap' => $dayMap,
            'defaultDayNumber' => $defaultDayNumber,
            'defaultDayLabel' => $defaultDayLabel,
        ]);
    }

    public function store(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:60'],
            'day_date_label' => ['required', 'string', 'max:100'],
            'time_slot' => ['required', 'string', 'max:50'],
            'subject_name' => ['required', 'string', 'max:120'],
        ], [
            'day_number.required' => 'Nomor urut hari wajib diisi.',
            'day_date_label.required' => 'Hari dan tanggal ujian wajib diisi (contoh: Senin, 01 Juni 2027).',
            'time_slot.required' => 'Jam ujian wajib diisi (contoh: 07.30-09.00).',
            'subject_name.required' => 'Nama mata pelajaran wajib diisi.',
        ]);

        $dayNumber = (int) $validated['day_number'];
        $dayLabel = trim($validated['day_date_label']);

        $maxOrderInDay = (int) $examSession->schedules()
            ->where('day_number', $dayNumber)
            ->max('sort_order');

        // Samakan label hari & tanggal pada hari ke-N yang sama agar penggabungan kolom tabel konsisten
        $examSession->schedules()
            ->where('day_number', $dayNumber)
            ->update(['day_date_label' => $dayLabel]);

        $examSession->schedules()->create([
            'day_number' => $dayNumber,
            'day_date_label' => $dayLabel,
            'time_slot' => trim($validated['time_slot']),
            'subject_name' => trim($validated['subject_name']),
            'sort_order' => $maxOrderInDay + 1,
        ]);

        return redirect()
            ->route('exam-sessions.schedules.index', $examSession)
            ->with('success', 'Jadwal mata pelajaran "' . trim($validated['subject_name']) . '" berhasil ditambahkan.');
    }

    public function seedSample(ExamSession $examSession): RedirectResponse
    {
        if ($examSession->schedules()->exists()) {
            return redirect()
                ->route('exam-sessions.schedules.index', $examSession)
                ->withErrors(['schedule' => 'Jadwal ujian sudah terisi. Kosongkan jadwal terlebih dahulu jika ingin mengisi contoh jadwal.']);
        }

        $samples = [
            ['day_number' => 1, 'day_date_label' => 'Senin, 01 Juni 2027', 'time_slot' => '07.30-09.00', 'subject_name' => 'Bahasa Indonesia', 'sort_order' => 1],
            ['day_number' => 1, 'day_date_label' => 'Senin, 01 Juni 2027', 'time_slot' => '09.00-10.00', 'subject_name' => 'SKI', 'sort_order' => 2],
            ['day_number' => 1, 'day_date_label' => 'Senin, 01 Juni 2027', 'time_slot' => '10.15-11.15', 'subject_name' => 'Fiqih', 'sort_order' => 3],
            ['day_number' => 2, 'day_date_label' => 'Selasa, 02 Juni 2027', 'time_slot' => '07.30-09.00', 'subject_name' => 'Bahasa Inggris', 'sort_order' => 1],
            ['day_number' => 2, 'day_date_label' => 'Selasa, 02 Juni 2027', 'time_slot' => '09.00-10.00', 'subject_name' => 'PKn', 'sort_order' => 2],
            ['day_number' => 2, 'day_date_label' => 'Selasa, 02 Juni 2027', 'time_slot' => '10.15-11.15', 'subject_name' => 'Seni Budaya', 'sort_order' => 3],
            ['day_number' => 3, 'day_date_label' => 'Rabu, 03 Juni 2027', 'time_slot' => '07.30-09.00', 'subject_name' => 'Matematika', 'sort_order' => 1],
            ['day_number' => 3, 'day_date_label' => 'Rabu, 03 Juni 2027', 'time_slot' => '09.00-10.00', 'subject_name' => 'Akidah Akhlak', 'sort_order' => 2],
            ['day_number' => 3, 'day_date_label' => 'Rabu, 03 Juni 2027', 'time_slot' => '10.15-11.15', 'subject_name' => 'Bahasa Sunda', 'sort_order' => 3],
            ['day_number' => 4, 'day_date_label' => 'Kamis, 04 Juni 2027', 'time_slot' => '07.30-09.00', 'subject_name' => 'IPA', 'sort_order' => 1],
            ['day_number' => 4, 'day_date_label' => 'Kamis, 04 Juni 2027', 'time_slot' => '09.00-10.00', 'subject_name' => "Qur'an Hadits", 'sort_order' => 2],
            ['day_number' => 4, 'day_date_label' => 'Kamis, 04 Juni 2027', 'time_slot' => '10.15-11.15', 'subject_name' => 'Prakarya', 'sort_order' => 3],
            ['day_number' => 5, 'day_date_label' => "Jum'at, 05 Juni 2027", 'time_slot' => '07.30-09.00', 'subject_name' => 'Bahasa Arab', 'sort_order' => 1],
            ['day_number' => 5, 'day_date_label' => "Jum'at, 05 Juni 2027", 'time_slot' => '09.00-10.00', 'subject_name' => 'BTQ', 'sort_order' => 2],
            ['day_number' => 6, 'day_date_label' => 'Sabtu, 06 Juni 2027', 'time_slot' => '07.30-09.00', 'subject_name' => 'IPS', 'sort_order' => 1],
            ['day_number' => 6, 'day_date_label' => 'Sabtu, 06 Juni 2027', 'time_slot' => '09.00-10.00', 'subject_name' => 'PJOK', 'sort_order' => 2],
        ];

        DB::transaction(function () use ($examSession, $samples) {
            foreach ($samples as $row) {
                $examSession->schedules()->create($row);
            }
        });

        return redirect()
            ->route('exam-sessions.schedules.index', $examSession)
            ->with('success', 'Contoh jadwal ujian 6 hari (16 mata pelajaran) berhasil dimuat.');
    }

    public function destroy(ExamSession $examSession, ExamSchedule $schedule): RedirectResponse
    {
        if ((int) $schedule->exam_session_id !== (int) $examSession->id) {
            abort(404);
        }

        $subject = $schedule->subject_name;
        $schedule->delete();

        return redirect()
            ->route('exam-sessions.schedules.index', $examSession)
            ->with('success', 'Baris jadwal "' . $subject . '" berhasil dihapus.');
    }

    public function clear(ExamSession $examSession): RedirectResponse
    {
        $deleted = $examSession->schedules()->delete();

        return redirect()
            ->route('exam-sessions.schedules.index', $examSession)
            ->with('success', 'Seluruh jadwal ujian (' . $deleted . ' baris) berhasil dikosongkan.');
    }
}