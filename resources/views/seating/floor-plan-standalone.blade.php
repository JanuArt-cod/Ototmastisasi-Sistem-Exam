<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denah Duduk Ujian - {{ $session->session_name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 14px;
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
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .fp-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: #dbeafe;
            color: #1e40af;
        }
        .fp-badge-final {
            background: #d1fae5;
            color: #065f46;
        }
        .fp-badge-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .fp-title {
            margin: 6px 0 4px 0;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        .fp-subtitle {
            margin: 0;
            font-size: 13px;
            color: #475569;
        }
        .fp-room-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 12px;
        }
        .fp-pill {
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .fp-pill.is-active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }
        .fp-toolbar {
            display: grid;
            grid-template-columns: 1fr 1.6fr 0.7fr;
            gap: 10px;
            align-items: end;
            margin-top: 12px;
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
        .fp-front-stage {
            text-align: center;
            padding: 11px 14px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .fp-desk-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(230px, 1fr));
            gap: 12px;
        }
        .fp-desk-grid[data-cols="2"] {
            grid-template-columns: repeat(2, minmax(230px, 1fr));
        }
        .fp-desk-grid[data-cols="3"] {
            grid-template-columns: repeat(3, minmax(230px, 1fr));
        }
        .fp-desk-grid[data-cols="4"] {
            grid-template-columns: repeat(4, minmax(200px, 1fr));
        }
        .fp-desk-card {
            border: 2px solid #94a3b8;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
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
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
        }
        .fp-seats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .fp-seat-slot {
            padding: 9px 8px;
            min-height: 88px;
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
            line-height: 1.28;
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
        .fp-other-room-hint {
            margin-top: 6px;
            font-size: 12px;
            color: #1e40af;
        }
        .fp-other-room-btn {
            display: inline-block;
            margin: 3px 4px 0 0;
            padding: 3px 8px;
            border-radius: 5px;
            border: 1px solid #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
        }
        .is-hidden {
            display: none !important;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .fp-toolbar {
                grid-template-columns: 1fr;
            }
            .fp-desk-grid,
            .fp-desk-grid[data-cols="2"],
            .fp-desk-grid[data-cols="3"],
            .fp-desk-grid[data-cols="4"] {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="fp-container">
        <div class="fp-card">
            <div>
                <span class="fp-badge">DENAH DUDUK UJIAN ONLINE</span>
                @if ($plan->status === 'finalized')
                    <span class="fp-badge fp-badge-final" style="margin-left: 4px;">FINAL #{{ $plan->version_number }}</span>
                @else
                    <span class="fp-badge fp-badge-draft" style="margin-left: 4px;">DRAFT #{{ $plan->version_number }}</span>
                @endif
                <h1 class="fp-title" id="fp-active-room-title">
                    {{ $roomsPayload[0]['room_name'] ?? 'Ruang Ujian' }}
                </h1>
                <p class="fp-subtitle">
                    <strong>{{ $session->school_name ?: 'Sekolah' }}</strong> &bull;
                    {{ $session->exam_title ?: $session->session_name }}
                    @if ($session->academic_year)
                        ({{ $session->academic_year }})
                    @endif
                </p>
            </div>

            {{-- TOMBOL CEPAT PILIH RUANGAN --}}
            <div class="fp-room-pills" id="fp-room-pills">
                @foreach ($roomsPayload as $idx => $r)
                    <button
                        type="button"
                        class="fp-pill {{ $idx === 0 ? 'is-active' : '' }}"
                        data-select-room="{{ $r['room_number'] }}"
                        onclick="switchRoom('{{ $r['room_number'] }}')"
                    >
                        {{ $r['room_name'] }} ({{ $r['total_students'] }})
                    </button>
                @endforeach
            </div>

            <div class="fp-toolbar">
                <div>
                    <label for="fp-room-select" class="fp-label">Pilih Ruangan</label>
                    <select id="fp-room-select" class="fp-select" onchange="switchRoom(this.value)">
                        @foreach ($roomsPayload as $r)
                            <option value="{{ $r['room_number'] }}">
                                {{ $r['room_name'] }} ({{ $r['total_students'] }} Peserta &bull; {{ $r['total_desks'] }} Meja)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fp-search-input" class="fp-label">Cari Nama Siswa, Kelas, atau Nomor Meja</label>
                    <input
                        type="text"
                        id="fp-search-input"
                        class="fp-input"
                        placeholder="Ketik nama siswa, kelas, atau kode meja (misal: .12)..."
                        oninput="filterAndHighlightSeats(this.value)"
                    >
                </div>

                <div>
                    <label for="fp-cols-select" class="fp-label">Kolom Meja (Layar Lebar)</label>
                    <select id="fp-cols-select" class="fp-select" onchange="changeGridColumns(this.value)">
                        <option value="2">2 Kolom</option>
                        <option value="3" selected>3 Kolom</option>
                        <option value="4">4 Kolom</option>
                    </select>
                </div>
            </div>

            <div id="fp-search-status" style="margin-top: 8px; font-size: 12.5px; font-weight: 600;"></div>
            <div id="fp-other-rooms-status" class="fp-other-room-hint"></div>
        </div>

        {{-- PANEL DENAH PER RUANGAN --}}
        @foreach ($roomsPayload as $idx => $room)
            <div
                class="fp-card {{ $idx === 0 ? '' : 'is-hidden' }}"
                data-room-panel
                data-room-number="{{ $room['room_number'] }}"
                data-room-slug="{{ $room['room_slug'] }}"
                data-room-title="{{ $room['room_name'] }} ({{ $room['total_students'] }} Peserta &bull; {{ $room['total_desks'] }} Meja)"
            >
                <div class="fp-front-stage">
                    DEPAN RUANGAN &bull; PAPAN TULIS &amp; MEJA PENGAWAS UJIAN
                </div>

                <div class="fp-desk-grid" data-cols="3">
                    @foreach ($room['desks'] as $desk)
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
        @endforeach
    </div>

    <script>
        let activeRoomNumber = @json((string) ($roomsPayload[0]['room_number'] ?? '1'));

        function switchRoom(targetRoom) {
            const target = String(targetRoom || '').trim().toLowerCase();
            const panels = document.querySelectorAll('[data-room-panel]');
            let matchedPanel = null;

            panels.forEach(function (panel) {
                const rNum = String(panel.getAttribute('data-room-number') || '').toLowerCase();
                const rSlug = String(panel.getAttribute('data-room-slug') || '').toLowerCase();
                if (target !== '' && (rNum === target || rSlug === target)) {
                    matchedPanel = panel;
                }
            });

            if (!matchedPanel && panels.length > 0) {
                matchedPanel = panels[0];
            }
            if (!matchedPanel) {
                return;
            }

            activeRoomNumber = String(matchedPanel.getAttribute('data-room-number'));

            panels.forEach(function (panel) {
                if (panel === matchedPanel) {
                    panel.classList.remove('is-hidden');
                } else {
                    panel.classList.add('is-hidden');
                }
            });

            const titleEl = document.getElementById('fp-active-room-title');
            if (titleEl) {
                titleEl.innerHTML = matchedPanel.getAttribute('data-room-title') || '';
            }

            const selectEl = document.getElementById('fp-room-select');
            if (selectEl) {
                selectEl.value = activeRoomNumber;
            }

            document.querySelectorAll('[data-select-room]').forEach(function (btn) {
                if (String(btn.getAttribute('data-select-room')) === activeRoomNumber) {
                    btn.classList.add('is-active');
                } else {
                    btn.classList.remove('is-active');
                }
            });

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('room', activeRoomNumber);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {
                // Abaikan jika dibuka langsung lewat protokol file://
            }

            const searchInput = document.getElementById('fp-search-input');
            if (searchInput && searchInput.value.trim() !== '') {
                filterAndHighlightSeats(searchInput.value);
            }
        }

        function changeGridColumns(cols) {
            document.querySelectorAll('.fp-desk-grid').forEach(function (grid) {
                grid.setAttribute('data-cols', String(cols));
            });
        }

        function filterAndHighlightSeats(rawQuery) {
            const q = (rawQuery || '').trim().toLowerCase();
            const statusEl = document.getElementById('fp-search-status');
            const otherStatusEl = document.getElementById('fp-other-rooms-status');
            let activeMatches = 0;
            const otherRoomMatches = [];

            document.querySelectorAll('[data-room-panel]').forEach(function (panel) {
                const rNum = String(panel.getAttribute('data-room-number'));
                const rPill = document.querySelector('[data-select-room="' + rNum + '"]');
                const rLabel = rPill ? rPill.textContent.trim() : ('Ruang ' + rNum);
                const isActive = (rNum === activeRoomNumber);
                let roomMatchCount = 0;

                panel.querySelectorAll('[data-desk-card]').forEach(function (card) {
                    let cardMatched = false;
                    card.querySelectorAll('[data-seat-search]').forEach(function (slot) {
                        const text = slot.getAttribute('data-seat-search') || '';
                        if (q !== '' && text.includes(q)) {
                            slot.classList.add('is-match');
                            cardMatched = true;
                            roomMatchCount++;
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

                if (isActive) {
                    activeMatches = roomMatchCount;
                } else if (q !== '' && roomMatchCount > 0) {
                    otherRoomMatches.push({
                        roomNumber: rNum,
                        label: rLabel,
                        count: roomMatchCount
                    });
                }
            });

            if (statusEl) {
                if (q === '') {
                    statusEl.textContent = '';
                } else if (activeMatches > 0) {
                    statusEl.style.color = '#065f46';
                    statusEl.textContent = 'Ditemukan ' + activeMatches + ' kursi cocok di ruangan aktif (ditandai kuning).';
                } else {
                    statusEl.style.color = '#991b1b';
                    statusEl.textContent = 'Tidak ditemukan di ruangan ini.';
                }
            }

            if (otherStatusEl) {
                if (q === '' || otherRoomMatches.length === 0) {
                    otherStatusEl.innerHTML = '';
                } else {
                    let html = 'Ditemukan di ruangan lain (ketuk untuk pindah): ';
                    otherRoomMatches.forEach(function (item) {
                        html += '<button type="button" class="fp-other-room-btn" onclick="switchRoom(\'' + item.roomNumber + '\')">'
                            + item.label + ' (' + item.count + ')</button>';
                    });
                    otherStatusEl.innerHTML = html;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            let initialRoom = '';
            try {
                const params = new URLSearchParams(window.location.search);
                initialRoom = params.get('room') || '';
            } catch (e) {}

            if (!initialRoom && window.location.hash) {
                initialRoom = window.location.hash.replace(/^#/, '');
            }

            if (initialRoom) {
                switchRoom(initialRoom);
            }
        });
    </script>
</body>
</html>