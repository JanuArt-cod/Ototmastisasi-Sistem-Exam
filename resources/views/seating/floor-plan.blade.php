<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denah Duduk {{ $activeRoom->room_name }} - {{ $session->session_name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 16px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        .fp-container {
            max-width: 1120px;
            margin: 0 auto;
        }
        .fp-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .fp-header-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .fp-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            background: #dbeafe;
            color: #1e40af;
        }
        .fp-badge-final {
            background: #d1fae5;
            color: #065f46;
        }
        .fp-title {
            margin: 6px 0 4px 0;
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
        }
        .fp-subtitle {
            margin: 0;
            font-size: 13.5px;
            color: #475569;
        }
        .fp-qr-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
        }
        .fp-qr-box img {
            width: 74px;
            height: 74px;
            display: block;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .fp-toolbar {
            display: grid;
            grid-template-columns: 1fr 1.4fr 0.7fr auto auto;
            gap: 10px;
            align-items: end;
        }
        .fp-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        .fp-input, .fp-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13.5px;
            background: #ffffff;
            color: #0f172a;
        }
        .fp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            cursor: pointer;
            white-space: nowrap;
        }
        .fp-btn:hover {
            background: #f8fafc;
        }
        .fp-btn-primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }
        .fp-btn-primary:hover {
            background: #1e293b;
        }
        .fp-front-stage {
            text-align: center;
            padding: 12px 16px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            letter-spacing: 0.6px;
            margin-bottom: 18px;
        }
        .fp-desk-grid {
            display: grid;
            grid-template-columns: repeat({{ $columns }}, minmax(230px, 1fr));
            gap: 14px;
        }
        .fp-desk-card {
            border: 2px solid #94a3b8;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .fp-desk-card.is-highlighted {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
        }
        .fp-desk-header {
            background: #e2e8f0;
            padding: 6px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
        }
        .fp-seats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .fp-seat-slot {
            padding: 10px 9px;
            min-height: 92px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .fp-seat-slot:first-child {
            border-right: 1px dashed #cbd5e1;
        }
        .fp-seat-slot.is-match {
            background: #fef08a;
        }
        .fp-seat-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #475569;
            margin-bottom: 4px;
        }
        .fp-seat-num {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
        .fp-student-name {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            margin-bottom: 4px;
            word-break: break-word;
        }
        .fp-desk-code {
            font-size: 11px;
            font-weight: 700;
            color: #1e40af;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }
        .fp-class-tag {
            font-size: 11px;
            color: #475569;
        }
        @media (max-width: 900px) {
            .fp-toolbar {
                grid-template-columns: 1fr;
            }
            .fp-desk-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="fp-container">
        {{-- HEADER DENAH DIGITAL --}}
        <div class="fp-card">
            <div class="fp-header-grid">
                <div>
                    <span class="fp-badge">DENAH DUDUK DIGITAL &bull; {{ strtoupper($activeRoom->room_name) }}</span>
                    @if ($plan->status === 'finalized')
                        <span class="fp-badge fp-badge-final" style="margin-left: 4px;">VERSI FINAL #{{ $plan->version_number }}</span>
                    @else
                        <span class="fp-badge" style="background: #fef3c7; color: #92400e; margin-left: 4px;">DRAFT #{{ $plan->version_number }}</span>
                    @endif
                    <h1 class="fp-title">{{ $activeRoom->room_name }} ({{ $totalRoomStudents }} Peserta &bull; {{ $desks->count() }} Meja)</h1>
                    <p class="fp-subtitle">
                        <strong>{{ $session->school_name ?: 'Sekolah' }}</strong> &bull;
                        {{ $session->exam_title ?: $session->session_name }}
                        @if ($session->academic_year)
                            ({{ $session->academic_year }})
                        @endif
                    </p>
                </div>

                <div class="fp-qr-box">
                    <img src="{{ $qrDataUri }}" alt="QR Code Denah {{ $activeRoom->room_name }}">
                    <div style="font-size: 12px; color: #334155; max-width: 210px; line-height: 1.4;">
                        <strong style="color: #0f172a; display: block;">Siap Unggah Online</strong>
                        Unduh file <code>.html</code> lalu unggah ke hosting statis agar pengawas bisa membuka via HP.
                    </div>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 16px 0;">

            {{-- KONTROL PILIH RUANGAN, KOLOM DENAH, CARI PESERTA & UNDUH HTML --}}
            <div class="fp-toolbar">
                <div>
                    <label for="room-switcher" class="fp-label">Pindah Ruangan</label>
                    <select
                        id="room-switcher"
                        class="fp-select"
                        onchange="window.location.href = this.value"
                    >
                        @foreach ($rooms as $r)
                            <option
                                value="{{ route('exam-sessions.seating.floor-plan', ['examSession' => $session->id, 'seatingPlan' => $plan->id, 'room' => $r->room_number, 'cols' => $columns]) }}"
                                {{ (int) $r->room_number === (int) $activeRoom->room_number ? 'selected' : '' }}
                            >
                                {{ $r->room_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fp-search-input" class="fp-label">Cari &amp; Sorot Posisi Peserta di {{ $activeRoom->room_name }}</label>
                    <input
                        type="text"
                        id="fp-search-input"
                        class="fp-input"
                        placeholder="Ketik nama siswa, kelas, atau nomor meja (misal: .12)..."
                        oninput="filterAndHighlightSeats(this.value)"
                    >
                </div>

                <div>
                    <label for="cols-switcher" class="fp-label">Kolom Meja</label>
                    <select
                        id="cols-switcher"
                        class="fp-select"
                        onchange="window.location.href = this.value"
                    >
                        @for ($c = 2; $c <= 5; $c++)
                            <option
                                value="{{ route('exam-sessions.seating.floor-plan', ['examSession' => $session->id, 'seatingPlan' => $plan->id, 'room' => $activeRoom->room_number, 'cols' => $c]) }}"
                                {{ $columns === $c ? 'selected' : '' }}
                            >
                                {{ $c }} Kolom
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <a
                        href="{{ route('exam-sessions.seating.floor-plan.export-html', ['examSession' => $session->id, 'seatingPlan' => $plan->id]) }}"
                        class="fp-btn fp-btn-primary"
                    >
                        Unduh HTML Denah Online
                    </a>
                </div>

                <div>
                    <a href="{{ route('exam-sessions.seating.index', ['examSession' => $session->id, 'plan_id' => $plan->id]) }}" class="fp-btn">
                        &larr; Kembali
                    </a>
                </div>
            </div>

            <div id="fp-search-status" style="margin-top: 8px; font-size: 12.5px; color: #1e40af; font-weight: 600;"></div>
        </div>

        {{-- AREA VISUAL DENAH RUANGAN --}}
        <div class="fp-card">
            <div class="fp-front-stage">
                DEPAN RUANGAN &bull; PAPAN TULIS &amp; MEJA PENGAWAS UJIAN
            </div>

            <div class="fp-desk-grid">
                @foreach ($desks as $desk)
                    <div class="fp-desk-card" data-desk-card>
                        <div class="fp-desk-header">
                            <span>MEJA {{ $desk['desk_number'] }}</span>
                            <span style="font-size: 11px; font-weight: 600; color: #475569;">
                                Urut
                                {{ $desk['seat_1']?->seat_number ?? '-' }}
                                @if ($desk['seat_2'])
                                    &amp; {{ $desk['seat_2']->seat_number }}
                                @endif
                            </span>
                        </div>

                        <div class="fp-seats-row">
                            @php $s1 = $desk['seat_1']; @endphp
                            <div
                                class="fp-seat-slot"
                                data-seat-search="{{ $s1 ? strtolower($s1->student_name_snapshot . ' ' . $s1->class_name_snapshot . ' ' . $s1->desk_participant_number) : '' }}"
                            >
                                @if ($s1)
                                    <div>
                                        <div class="fp-seat-meta">
                                            <span>Kursi 1</span>
                                            <span class="fp-seat-num">#{{ $s1->seat_number }}</span>
                                        </div>
                                        <div class="fp-student-name">{{ $s1->student_name_snapshot }}</div>
                                    </div>
                                    <div>
                                        <div class="fp-desk-code">{{ $s1->desk_participant_number }}</div>
                                        <div class="fp-class-tag">Kelas: <strong>{{ $s1->class_name_snapshot }}</strong></div>
                                    </div>
                                @else
                                    <div style="font-size: 12px; color: #94a3b8; margin: auto;">Kosong</div>
                                @endif
                            </div>

                            @php $s2 = $desk['seat_2']; @endphp
                            <div
                                class="fp-seat-slot"
                                data-seat-search="{{ $s2 ? strtolower($s2->student_name_snapshot . ' ' . $s2->class_name_snapshot . ' ' . $s2->desk_participant_number) : '' }}"
                            >
                                @if ($s2)
                                    <div>
                                        <div class="fp-seat-meta">
                                            <span>Kursi 2</span>
                                            <span class="fp-seat-num">#{{ $s2->seat_number }}</span>
                                        </div>
                                        <div class="fp-student-name">{{ $s2->student_name_snapshot }}</div>
                                    </div>
                                    <div>
                                        <div class="fp-desk-code">{{ $s2->desk_participant_number }}</div>
                                        <div class="fp-class-tag">Kelas: <strong>{{ $s2->class_name_snapshot }}</strong></div>
                                    </div>
                                @else
                                    <div style="font-size: 12px; color: #94a3b8; margin: auto;">Kosong</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function filterAndHighlightSeats(rawQuery) {
            const q = (rawQuery || '').trim().toLowerCase();
            const statusEl = document.getElementById('fp-search-status');
            const deskCards = document.querySelectorAll('[data-desk-card]');
            let matchCount = 0;

            deskCards.forEach(function (card) {
                let cardMatched = false;
                const slots = card.querySelectorAll('[data-seat-search]');
                slots.forEach(function (slot) {
                    const text = slot.getAttribute('data-seat-search') || '';
                    if (q !== '' && text.includes(q)) {
                        slot.classList.add('is-match');
                        cardMatched = true;
                        matchCount++;
                    } else {
                        slot.classList.remove('is-match');
                    }
                });

                if (cardMatched) {
                    card.classList.add('is-highlighted');
                } else {
                    card.classList.remove('is-highlighted');
                }
            });

            if (statusEl) {
                if (q === '') {
                    statusEl.textContent = '';
                } else if (matchCount > 0) {
                    statusEl.style.color = '#065f46';
                    statusEl.textContent = 'Ditemukan ' + matchCount + ' kursi yang cocok (ditandai warna kuning).';
                } else {
                    statusEl.style.color = '#991b1b';
                    statusEl.textContent = 'Tidak ditemukan peserta yang cocok di ruangan ini. Coba pindah ruangan di pilihan sebelah kiri.';
                }
            }
        }
    </script>
</body>
</html>