@extends('layouts.app')

@section('title', 'Pengaturan Ruang Ujian - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Pengaturan Ruang Ujian</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Bagikan ruang ujian secara acak (random) atau berurutan dengan kapasitas sama rata maupun kapasitas berbeda tiap ruangan.
                </p>
            </div>
            <div class="actions-group">
                <a href="{{ route('exam-sessions.students.index', $session) }}" class="btn btn-secondary">
                    Kelola Data Siswa
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Total Siswa Valid</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($totalValidStudents, 0, ',', '.') }} Siswa
                </div>
            </div>

            <div style="background: var(--success-bg); padding: 12px 14px; border-radius: 6px; border: 1px solid var(--success-border);">
                <div style="font-size: 12px; color: var(--success-text);">Sudah Memiliki Ruang</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--success-text); margin-top: 2px;">
                    {{ number_format($studentsWithRoom, 0, ',', '.') }} Siswa
                </div>
            </div>

            <div style="background: #fffbeb; padding: 12px 14px; border-radius: 6px; border: 1px solid #fde68a;">
                <div style="font-size: 12px; color: #92400e;">Ruang Masih Kosong</div>
                <div style="font-size: 18px; font-weight: 700; color: #92400e; margin-top: 2px;">
                    {{ number_format($studentsWithoutRoom, 0, ',', '.') }} Siswa
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Jumlah Ruang Aktif</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($currentRooms->count(), 0, ',', '.') }} Ruangan
                </div>
            </div>
        </div>
    </section>

    {{-- FORMULIR KONFIGURASI PEMBAGIAN RUANG UJIAN --}}
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
            <div>
                <h2 class="card-title" style="margin-bottom: 2px;">1. Aturan Pembagian Ruang Ujian</h2>
                <p class="card-desc" style="margin-bottom: 0;">
                    Pilih mode pembagian kapasitas (sama rata atau berbeda per ruangan) serta metode penempatan siswa sebelum membuat pratinjau.
                </p>
            </div>

            @if ($studentsWithRoom > 0)
                <form
                    action="{{ route('exam-sessions.rooms.clear', $session) }}"
                    method="POST"
                    onsubmit="return confirm('Kosongkan data ruang ujian untuk seluruh siswa pada sesi ini?')"
                >
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        Kosongkan Semua Ruang
                    </button>
                </form>
            @endif
        </div>

        @php
            $savedCfg = $preview['config'] ?? [];
            $selectedMode = old('allocation_mode', $savedCfg['allocation_mode'] ?? 'by_capacity');
            $selectedMethod = old('distribution_method', $savedCfg['distribution_method'] ?? 'random');
            $selectedScope = old('target_scope', $savedCfg['target_scope'] ?? ($studentsWithoutRoom > 0 ? 'empty_only' : 'all'));

            $customRoomsList = old('custom_rooms');
            if (! is_array($customRoomsList) or empty($customRoomsList)) {
                $customRoomsList = ! empty($savedCfg['custom_rooms'])
                    ? $savedCfg['custom_rooms']
                    : [
                        ['name' => 'Ruang 01', 'capacity' => 20],
                        ['name' => 'Ruang 02', 'capacity' => 25],
                        ['name' => 'Ruang 03', 'capacity' => 20],
                    ];
            }
        @endphp

        <form action="{{ route('exam-sessions.rooms.preview', $session) }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Awalan Nama Ruang</label>
                    <input
                        type="text"
                        id="room_prefix"
                        name="room_prefix"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('room_prefix', $savedCfg['room_prefix'] ?? 'Ruang') }}"
                        placeholder="Contoh: Ruang atau R-"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Nomor Awal Ruang</label>
                    <input
                        type="number"
                        min="1"
                        max="9999"
                        id="start_number"
                        name="start_number"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('start_number', $savedCfg['start_number'] ?? 1) }}"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Panjang Digit Nomor Ruang</label>
                    <select id="digit_length" name="digit_length" class="input-text" style="min-width: 100%; width: 100%;">
                        @php $digit = (int) old('digit_length', $savedCfg['digit_length'] ?? 2); @endphp
                        <option value="1" {{ $digit === 1 ? 'selected' : '' }}>1 Digit (Ruang 1, Ruang 2)</option>
                        <option value="2" {{ $digit === 2 ? 'selected' : '' }}>2 Digit (Ruang 01, Ruang 02)</option>
                        <option value="3" {{ $digit === 3 ? 'selected' : '' }}>3 Digit (Ruang 001, Ruang 002)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Mode Kapasitas Ruangan</label>
                    <select
                        id="allocation_mode"
                        name="allocation_mode"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        onchange="toggleAllocationMode()"
                    >
                        <option value="by_capacity" {{ $selectedMode === 'by_capacity' ? 'selected' : '' }}>
                            Kapasitas Sama per Ruang
                        </option>
                        <option value="custom_per_room" {{ $selectedMode === 'custom_per_room' ? 'selected' : '' }}>
                            Kapasitas Berbeda per Ruangan (Atur Tiap Ruang)
                        </option>
                        <option value="by_room_count" {{ $selectedMode === 'by_room_count' ? 'selected' : '' }}>
                            Bagi Rata ke Jumlah Ruang Tersedia
                        </option>
                    </select>
                </div>

                <div id="box_capacity" style="display: {{ $selectedMode === 'by_capacity' ? 'block' : 'none' }};">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Kapasitas Siswa per Ruang</label>
                    <input
                        type="number"
                        min="1"
                        max="500"
                        name="capacity_per_room"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('capacity_per_room', $savedCfg['capacity_per_room'] ?? 20) }}"
                    >
                </div>

                <div id="box_room_count" style="display: {{ $selectedMode === 'by_room_count' ? 'block' : 'none' }};">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Jumlah Total Ruang Ujian</label>
                    <input
                        type="number"
                        min="1"
                        max="500"
                        name="total_rooms"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('total_rooms', $savedCfg['total_rooms'] ?? 10) }}"
                    >
                </div>

                <div id="box_custom_info" style="display: {{ $selectedMode === 'custom_per_room' ? 'block' : 'none' }};">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Ringkasan Kapasitas Kustom</label>
                    <div id="custom-capacity-badge" style="padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: var(--bg-muted); font-size: 13px; font-weight: 600;">
                        Menghitung kursi...
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Metode Penempatan Siswa</label>
                    <select name="distribution_method" class="input-text" style="min-width: 100%; width: 100%;">
                        <option value="random" {{ $selectedMethod === 'random' ? 'selected' : '' }}>
                            Acak Menyeluruh (Random)
                        </option>
                        <option value="random_per_class" {{ $selectedMethod === 'random_per_class' ? 'selected' : '' }}>
                            Acak Merata Lintas Kelas (Campur Rombel)
                        </option>
                        <option value="sequential" {{ $selectedMethod === 'sequential' ? 'selected' : '' }}>
                            Urut Berdasarkan Kelas &amp; Nama
                        </option>
                    </select>
                </div>
            </div>

            {{-- PANEL PENGATURAN KAPASITAS BERBEDA PER RUANGAN --}}
            <div
                id="box_custom_rooms"
                style="display: {{ $selectedMode === 'custom_per_room' ? 'block' : 'none' }}; background: var(--bg-muted); padding: 14px 16px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 16px;"
            >
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700;">Daftar Ruangan dan Kapasitas Masing-Masing</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Ubah nama ruang maupun kapasitas siswa per baris sesuai kebutuhan (contoh: Ruang 01 = 20, Ruang 02 = 25).
                        </div>
                    </div>

                    <div class="actions-group">
                        <input
                            type="number"
                            id="bulk_room_count"
                            min="1"
                            max="200"
                            value="{{ count($customRoomsList) }}"
                            class="input-text"
                            style="width: 72px; min-width: 72px;"
                            title="Jumlah baris ruangan"
                        >
                        <button type="button" class="btn btn-secondary btn-sm" onclick="generateCustomRoomRows()">
                            Buat Daftar Otomatis
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addCustomRoomRow()">
                            + Tambah Ruangan
                        </button>
                    </div>
                </div>

                <div style="max-height: 320px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">No.</th>
                                <th style="width: 48%;">Nama Ruangan</th>
                                <th style="width: 26%;">Kapasitas (Siswa)</th>
                                <th style="width: 16%;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody id="custom-rooms-tbody">
                            @foreach ($customRoomsList as $idx => $roomRow)
                                <tr>
                                    <td class="row-num"><strong>{{ $idx + 1 }}</strong></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="custom_rooms[{{ $idx }}][name]"
                                            class="input-text custom-room-name"
                                            style="min-width: 100%; width: 100%;"
                                            value="{{ $roomRow['name'] ?? '' }}"
                                            placeholder="Contoh: Ruang 01"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="1"
                                            max="500"
                                            name="custom_rooms[{{ $idx }}][capacity]"
                                            class="input-text custom-room-cap"
                                            style="min-width: 100%; width: 100%;"
                                            value="{{ $roomRow['capacity'] ?? 20 }}"
                                            oninput="recalculateCustomCapacity()"
                                        >
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeCustomRoomRow(this)">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="custom-capacity-footer" style="margin-top: 10px; font-size: 12px; font-weight: 600;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600;">
                        <input type="radio" name="target_scope" value="empty_only" onchange="recalculateCustomCapacity()" {{ $selectedScope === 'empty_only' ? 'checked' : '' }}>
                        <span>Hanya siswa yang ruangnya masih kosong ({{ number_format($studentsWithoutRoom, 0, ',', '.') }} siswa)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="target_scope" value="all" onchange="recalculateCustomCapacity()" {{ $selectedScope === 'all' ? 'checked' : '' }}>
                        <span>Atur ulang semua siswa valid ({{ number_format($totalValidStudents, 0, ',', '.') }} siswa)</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" {{ $totalValidStudents === 0 ? 'disabled' : '' }}>
                    Buat Pratinjau Pembagian Ruang
                </button>
            </div>
        </form>
    </section>

    {{-- BAGIAN 2: PRATINJAU PEMBAGIAN RUANG SEBELUM DISIMPAN --}}
    @if (! empty($preview))
        <section class="card" style="border-color: #93c5fd; background: #f8fafc;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px;">
                <div>
                    <span class="badge badge-info" style="margin-bottom: 4px;">Pratinjau Belum Disimpan</span>
                    <h2 class="card-title" style="margin-bottom: 2px;">
                        Hasil Simulasi: {{ number_format($preview['total_assigned'], 0, ',', '.') }} Siswa ke dalam {{ $preview['total_rooms_used'] }} Ruangan
                    </h2>
                    <p class="card-desc" style="margin-bottom: 0;">
                        Periksa ringkasan kapasitas tiap ruangan dan contoh penempatan siswa di bawah ini sebelum menerapkan ke database.
                    </p>
                </div>

                <form action="{{ route('exam-sessions.rooms.apply', $session) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Terapkan &amp; Simpan Pembagian Ruang
                    </button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 18px;">
                <div>
                    <h3 style="font-size: 14px; margin: 0 0 8px;">Ringkasan Terisi / Kapasitas per Ruangan</h3>
                    <div style="max-height: 360px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Nama Ruang</th>
                                    <th style="width: 24%;">Terisi / Kapasitas</th>
                                    <th style="width: 46%;">Komposisi Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preview['room_summary'] as $roomItem)
                                    <tr>
                                        <td><strong>{{ $roomItem['room_name'] }}</strong></td>
                                        <td>
                                            {{ $roomItem['student_count'] }}
                                            @if (! empty($roomItem['capacity']))
                                                / {{ $roomItem['capacity'] }}
                                            @endif
                                            siswa
                                        </td>
                                        <td style="font-size: 12px; color: var(--text-secondary);">{{ $roomItem['classes_text'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h3 style="font-size: 14px; margin: 0 0 8px;">Contoh Penempatan Siswa (25 Data Pertama)</h3>
                    <div style="max-height: 360px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 22%;">No. Peserta</th>
                                    <th style="width: 34%;">Nama Siswa</th>
                                    <th style="width: 20%;">Kelas</th>
                                    <th style="width: 24%;">Ruang Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preview['sample_rows'] as $row)
                                    <tr>
                                        <td>{{ $row['participant_number'] }}</td>
                                        <td><strong>{{ $row['student_name'] }}</strong></td>
                                        <td>{{ $row['class_name'] }}</td>
                                        <td><span class="badge badge-success">{{ $row['new_room_name'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- BAGIAN 3: DAFTAR RUANG UJIAN YANG SAAT INI TERSIMPAN DI DATABASE --}}
    <section class="card">
        <h2 class="card-title">2. Daftar Ruang Ujian yang Aktif Tersimpan</h2>
        <p class="card-desc">
            Rekapitulasi ruangan ujian yang saat ini tercatat pada data siswa sesi ini.
        </p>

        @if ($currentRooms->isEmpty())
            <div style="background: var(--bg-muted); padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; color: var(--text-secondary);">
                Belum ada siswa yang memiliki data ruang ujian. Gunakan formulir di atas untuk membagikan ruang ujian secara otomatis atau acak.
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                @foreach ($currentRooms as $room)
                    <div style="background: var(--bg-muted); padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <strong>{{ $room->room_name }}</strong>
                        <span class="badge badge-info">{{ number_format($room->total, 0, ',', '.') }} siswa</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        const emptyStudentsCount = {{ (int) $studentsWithoutRoom }};
        const allValidStudentsCount = {{ (int) $totalValidStudents }};

        function toggleAllocationMode() {
            const mode = document.getElementById('allocation_mode').value;
            document.getElementById('box_capacity').style.display = mode === 'by_capacity' ? 'block' : 'none';
            document.getElementById('box_room_count').style.display = mode === 'by_room_count' ? 'block' : 'none';
            document.getElementById('box_custom_info').style.display = mode === 'custom_per_room' ? 'block' : 'none';
            document.getElementById('box_custom_rooms').style.display = mode === 'custom_per_room' ? 'block' : 'none';
            recalculateCustomCapacity();
        }

        function formatRoomLabel(offset) {
            let prefix = (document.getElementById('room_prefix').value || 'Ruang').trim();
            if (prefix !== '' && /[a-zA-Z0-9]$/.test(prefix)) {
                prefix += ' ';
            }
            const startNum = Math.max(1, parseInt(document.getElementById('start_number').value || '1', 10));
            const digitLen = Math.max(1, parseInt(document.getElementById('digit_length').value || '2', 10));
            const numStr = String(startNum + offset).padStart(digitLen, '0');
            return prefix + numStr;
        }

        function addCustomRoomRow(customName = null, customCap = 20) {
            const tbody = document.getElementById('custom-rooms-tbody');
            const index = tbody.rows.length;
            const roomName = customName !== null ? customName : formatRoomLabel(index);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="row-num"><strong>${index + 1}</strong></td>
                <td>
                    <input type="text" name="custom_rooms[${index}][name]" class="input-text custom-room-name" style="min-width: 100%; width: 100%;" value="${escapeHtml(roomName)}" placeholder="Contoh: Ruang 01">
                </td>
                <td>
                    <input type="number" min="1" max="500" name="custom_rooms[${index}][capacity]" class="input-text custom-room-cap" style="min-width: 100%; width: 100%;" value="${customCap}" oninput="recalculateCustomCapacity()">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeCustomRoomRow(this)">Hapus</button>
                </td>
            `;
            tbody.appendChild(tr);
            recalculateCustomCapacity();
        }

        function removeCustomRoomRow(btn) {
            const tbody = document.getElementById('custom-rooms-tbody');
            if (tbody.rows.length <= 1) {
                alert('Minimal harus terdapat 1 baris ruangan.');
                return;
            }
            btn.closest('tr').remove();
            reindexCustomRows();
            recalculateCustomCapacity();
        }

        function generateCustomRoomRows() {
            const countInput = document.getElementById('bulk_room_count');
            const count = Math.min(200, Math.max(1, parseInt(countInput.value || '5', 10)));
            const tbody = document.getElementById('custom-rooms-tbody');
            tbody.innerHTML = '';

            for (let i = 0; i < count; i++) {
                addCustomRoomRow(formatRoomLabel(i), 20);
            }
            recalculateCustomCapacity();
        }

        function reindexCustomRows() {
            const tbody = document.getElementById('custom-rooms-tbody');
            Array.from(tbody.rows).forEach((row, idx) => {
                const numCell = row.querySelector('.row-num');
                if (numCell) {
                    numCell.innerHTML = `<strong>${idx + 1}</strong>`;
                }
                const nameInput = row.querySelector('.custom-room-name');
                const capInput = row.querySelector('.custom-room-cap');
                if (nameInput) nameInput.name = `custom_rooms[${idx}][name]`;
                if (capInput) capInput.name = `custom_rooms[${idx}][capacity]`;
            });
        }

        function recalculateCustomCapacity() {
            const tbody = document.getElementById('custom-rooms-tbody');
            const badge = document.getElementById('custom-capacity-badge');
            const footer = document.getElementById('custom-capacity-footer');
            if (!tbody || !badge || !footer) return;

            let totalSeats = 0;
            const roomCount = tbody.rows.length;
            Array.from(tbody.querySelectorAll('.custom-room-cap')).forEach((inp) => {
                const val = parseInt(inp.value || '0', 10);
                if (val > 0) totalSeats += val;
            });

            const scopeRadio = document.querySelector('input[name="target_scope"]:checked');
            const targetStudents = (scopeRadio && scopeRadio.value === 'all') ? allValidStudentsCount : emptyStudentsCount;

            badge.textContent = `${totalSeats.toLocaleString('id-ID')} Kursi (${roomCount} Ruang)`;

            if (totalSeats >= targetStudents) {
                badge.style.background = 'var(--success-bg)';
                badge.style.borderColor = 'var(--success-border)';
                badge.style.color = 'var(--success-text)';
                footer.style.color = 'var(--success-text)';
                footer.textContent = `Kapasitas mencukupi: tersedia ${totalSeats.toLocaleString('id-ID')} kursi untuk ${targetStudents.toLocaleString('id-ID')} siswa target.`;
            } else {
                const diff = targetStudents - totalSeats;
                badge.style.background = '#fffbeb';
                badge.style.borderColor = '#fde68a';
                badge.style.color = '#92400e';
                footer.style.color = '#b91c1c';
                footer.textContent = `Perhatian: total kapasitas (${totalSeats.toLocaleString('id-ID')} kursi) masih kurang ${diff.toLocaleString('id-ID')} kursi dari ${targetStudents.toLocaleString('id-ID')} siswa target.`;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function () {
            recalculateCustomCapacity();
        });
    </script>
@endsection