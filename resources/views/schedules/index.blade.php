@extends('layouts.app')

@section('title', 'Jadwal Ujian (Sisi Belakang) - ' . $session->session_name)

@section('content')
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 6px;">Jadwal Ujian &bull; Sisi Belakang Kartu</span>
                <h1 class="card-title" style="font-size: 20px;">{{ $session->session_name }}</h1>
                <p class="card-desc" style="margin-bottom: 0;">
                    Atur daftar hari, tanggal, jam, dan mata pelajaran yang akan dicetak pada sisi belakang kartu peserta ujian.
                </p>
            </div>
            <div class="actions-group">

                <a href="{{ route('exam-sessions.schedules.share.index', $session) }}" class="btn btn-primary">
                    Cetak &amp; Share Jadwal (WA)
                </a>
                <a href="{{ route('exam-sessions.design.edit', $session) }}" class="btn btn-secondary">
                    Desain Kartu &amp; Cetak
                </a>
                <a href="{{ route('exam-sessions.show', $session) }}" class="btn btn-secondary">
                    Kembali ke Sesi
                </a>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 18px 0;">

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Total Hari Ujian</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($groupedDays->count(), 0, ',', '.') }} Hari
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Total Mata Pelajaran</div>
                <div style="font-size: 18px; font-weight: 700; margin-top: 2px;">
                    {{ number_format($schedules->count(), 0, ',', '.') }} Mapel
                </div>
            </div>

            <div style="background: var(--bg-muted); padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 12px; color: var(--text-secondary);">Cakupan Berlaku</div>
                <div style="font-size: 15px; font-weight: 700; margin-top: 4px;">
                    Seluruh Siswa Sesi Ini
                </div>
            </div>
        </div>
    </section>

    {{-- FORMULIR TAMBAH BARIS JADWAL --}}
    <section class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <div>
                <h2 class="card-title" style="margin-bottom: 2px;">1. Tambah Mata Pelajaran ke Jadwal Ujian</h2>
                <p class="card-desc" style="margin-bottom: 0;">
                    Gunakan angka <strong>Hari Ke-</strong> yang sama untuk menambahkan beberapa jam mata pelajaran pada hari ujian yang sama.
                </p>
            </div>

            <div class="actions-group">
                @if ($schedules->isEmpty())
                    <form
                        action="{{ route('exam-sessions.schedules.seed-sample', $session) }}"
                        method="POST"
                        onsubmit="return confirm('Isi contoh jadwal ujian 6 hari untuk pengujian?')"
                    >
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            Isi Contoh Jadwal (6 Hari)
                        </button>
                    </form>
                @else
                    <form
                        action="{{ route('exam-sessions.schedules.clear', $session) }}"
                        method="POST"
                        onsubmit="return confirm('Hapus seluruh jadwal ujian pada sesi ini?')"
                    >
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            Kosongkan Semua Jadwal
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <form action="{{ route('exam-sessions.schedules.store', $session) }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: 0.7fr 1.6fr 1.2fr 1.8fr auto; gap: 10px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Hari Ke-</label>
                    <input
                        type="number"
                        id="day_number"
                        name="day_number"
                        min="1"
                        max="60"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('day_number', $defaultDayNumber) }}"
                        oninput="syncDayLabel()"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Hari &amp; Tanggal</label>
                    <input
                        type="text"
                        id="day_date_label"
                        name="day_date_label"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('day_date_label', $defaultDayLabel) }}"
                        placeholder="Contoh: Senin, 01 Juni 2027"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Jam Ujian</label>
                    <input
                        type="text"
                        name="time_slot"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('time_slot') }}"
                        placeholder="Contoh: 07.30-09.00"
                        required
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Mata Pelajaran</label>
                    <input
                        type="text"
                        name="subject_name"
                        class="input-text"
                        style="min-width: 100%; width: 100%;"
                        value="{{ old('subject_name') }}"
                        placeholder="Contoh: Bahasa Indonesia"
                        required
                    >
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">
                        + Tambah
                    </button>
                </div>
            </div>
        </form>
    </section>

    {{-- TABEL JADWAL TERGABUNG & KELOLA BARIS --}}
    <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 18px; align-items: start;">
        <section class="card">
            <h2 class="card-title">2. Pratinjau Tabel Jadwal Sisi Belakang</h2>
            <p class="card-desc">
                Susunan 4 kolom yang akan dicetak pada sisi belakang kartu peserta ujian.
            </p>

            @if ($groupedDays->isEmpty())
                <div style="background: var(--bg-muted); padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; color: var(--text-secondary);">
                    Belum ada jadwal ujian. Tambahkan mata pelajaran di atas atau klik <strong>Isi Contoh Jadwal (6 Hari)</strong> untuk melihat pratinjau.
                </div>
            @else
                <table style="border-collapse: collapse; width: 100%; font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th style="width: 10%; text-align: center; border: 1px solid #94a3b8;">NO</th>
                            <th style="width: 32%; border: 1px solid #94a3b8;">HARI &amp; TANGGAL</th>
                            <th style="width: 24%; text-align: center; border: 1px solid #94a3b8;">JAM</th>
                            <th style="width: 34%; border: 1px solid #94a3b8;">MATA PELAJARAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedDays as $dayItems)
                            @php $rowCount = $dayItems->count(); @endphp
                            @foreach ($dayItems as $idx => $item)
                                <tr>
                                    @if ($idx === 0)
                                        <td rowspan="{{ $rowCount }}" style="text-align: center; vertical-align: middle; border: 1px solid #94a3b8; font-weight: 700;">
                                            {{ $item->day_number }}
                                        </td>
                                        <td rowspan="{{ $rowCount }}" style="vertical-align: middle; border: 1px solid #94a3b8; font-weight: 600;">
                                            {{ $item->day_date_label }}
                                        </td>
                                    @endif
                                    <td style="text-align: center; border: 1px solid #94a3b8;">{{ $item->time_slot }}</td>
                                    <td style="border: 1px solid #94a3b8;">{{ $item->subject_name }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="card">
            <h2 class="card-title">3. Rincian Baris Jadwal Tersimpan</h2>
            <p class="card-desc">
                Hapus baris tertentu apabila ada kekeliruan jam atau nama mata pelajaran.
            </p>

            @if ($schedules->isEmpty())
                <div style="background: var(--bg-muted); padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; color: var(--text-secondary);">
                    Daftar baris jadwal masih kosong.
                </div>
            @else
                <div style="max-height: 440px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 12%;">Hari</th>
                                <th style="width: 28%;">Tanggal</th>
                                <th style="width: 22%;">Jam</th>
                                <th style="width: 26%;">Mata Pelajaran</th>
                                <th style="width: 12%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $row)
                                <tr>
                                    <td><strong>{{ $row->day_number }}</strong></td>
                                    <td style="font-size: 12px;">{{ $row->day_date_label }}</td>
                                    <td style="font-size: 12px;">{{ $row->time_slot }}</td>
                                    <td><strong>{{ $row->subject_name }}</strong></td>
                                    <td>
                                        <form
                                            action="{{ route('exam-sessions.schedules.destroy', [$session, $row]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus jadwal {{ $row->subject_name }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <script>
        const existingDayLabels = @json($dayMap);

        function syncDayLabel() {
            const dayInput = document.getElementById('day_number');
            const labelInput = document.getElementById('day_date_label');
            if (!dayInput || !labelInput) return;

            const dayVal = String(dayInput.value || '').trim();
            if (existingDayLabels[dayVal]) {
                labelInput.value = existingDayLabels[dayVal];
            }
        }
    </script>
@endsection