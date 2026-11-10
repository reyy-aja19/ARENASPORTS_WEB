@extends('layouts.admin', [
    'title' => 'Manajemen Booking',
    'heading' => 'Management Booking'
])

@section('content')

@if(session('success'))
    <div class="surface" style="padding:14px 18px;margin-bottom:16px;color:#166534;background:#dcfce7;border-radius:10px;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="surface" style="padding:14px 18px;margin-bottom:16px;color:#991b1b;background:#fee2e2;border-radius:10px;">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="surface" style="padding:14px 18px;margin-bottom:16px;color:#991b1b;background:#fee2e2;border-radius:10px;">
        <strong>Terjadi kesalahan:</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- FORM TAMBAH BOOKING --}}
<form class="surface compact-form"
      action="{{ route('admin.bookings.store') }}"
      method="POST">

    @csrf

    <h3 style="margin-bottom:18px;">Tambah Booking Manual</h3>

    <div class="grid-4">

        <div class="form-group">
            <label>User</label>
            <select class="select-ui" name="user_id" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        {{ old('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Lapangan</label>
            <select class="select-ui" name="lapangan_id" required>
                @foreach($courts as $court)
                    <option value="{{ $court->id }}"
                        {{ old('lapangan_id') == $court->id ? 'selected' : '' }}>
                        {{ $court->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Metode Pembayaran</label>
            <select class="select-ui" name="payment_method" required>
                @foreach(['Transfer', 'Virtual Account', 'E-Wallet', 'Cash'] as $method)
                    <option value="{{ $method }}"
                        {{ old('payment_method') == $method ? 'selected' : '' }}>
                        {{ $method }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal</label>
            <input class="input-ui"
                   type="date"
                   name="booking_date"
                   value="{{ old('booking_date', date('Y-m-d', strtotime('+1 day'))) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Jam Mulai</label>
            <input class="input-ui"
                   type="time"
                   name="start_time"
                   value="{{ old('start_time', '08:00') }}"
                   required>
        </div>

        <div class="form-group">
            <label>Jam Selesai</label>
            <input class="input-ui"
                   type="time"
                   name="end_time"
                   value="{{ old('end_time', '09:00') }}"
                   required>
        </div>

        <div class="form-group">
            <label>Durasi (jam)</label>
            <input class="input-ui"
                   type="number"
                   min="1"
                   name="hours"
                   value="{{ old('hours', 1) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select class="select-ui" name="status" required>
                <option value="Menunggu">Menunggu</option>
                <option value="Dibatalkan">Dibatalkan</option>
            </select>
        </div>

    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:16px;">
        <button class="btn-ui btn-green" type="submit">
            Tambah Booking
        </button>
    </div>
</form>


{{-- TABEL DATA BOOKING --}}
<div class="data-table mt-16">

    <div style="padding:16px 18px;">
        <h3>Daftar Booking Lapangan</h3>
        <p style="margin-top:5px;color:#64748b;">
            Kelola booking, pembayaran, dan perpanjangan waktu sewa lapangan.
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Pembayaran</th>
                <th>Booking</th>
                <th>Waktu</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

            @forelse($bookings as $i => $booking)

                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        <strong>{{ $booking->user?->name ?? '-' }}</strong>
                    </td>

                    <td>
                        {{ $booking->payment_method ?? '-' }}
                    </td>

                    <td>
                        {{ $booking->lapangan?->nama ?? '-' }}
                    </td>

                    <td>
                        {{ $booking->booking_date?->format('d M Y') ?? '-' }}
                        <br>
                        <small>
                            {{ substr($booking->start_time, 0, 5) }}
                            -
                            {{ substr($booking->end_time, 0, 5) }}
                        </small>
                    </td>

                    <td>
                        Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                    </td>

                    <td>
                        @if($booking->status === 'Lunas')
                            <span style="color:#15803d;font-weight:700;">Lunas</span>
                        @elseif($booking->status === 'Menunggu')
                            <span style="color:#b45309;font-weight:700;">Menunggu</span>
                        @elseif($booking->status === 'Dibatalkan')
                            <span style="color:#dc2626;font-weight:700;">Dibatalkan</span>
                        @else
                            {{ $booking->status }}
                        @endif
                    </td>

                    <td>
                        <div class="btn-row wrap">

                            {{-- HAPUS BOOKING --}}
                            <form method="POST"
                                  action="{{ route('admin.bookings.delete', $booking) }}"
                                  onsubmit="return confirm('Yakin ingin menghapus booking ini?')">

                                @csrf
                                @method('DELETE')

                                <button class="btn-ui btn-red" type="submit">
                                    Hapus
                                </button>
                            </form>

                            {{-- PERPANJANG BOOKING --}}
                            @if($booking->status === 'Lunas')

                                <button class="btn-ui btn-green"
                                        type="button"
                                        onclick="toggleExtension('extension-{{ $booking->id }}')">
                                    Perpanjang
                                </button>

                            @endif

                        </div>
                    </td>
                </tr>

                {{-- FORM PERPANJANGAN --}}
                @if($booking->status === 'Lunas')

                    <tr id="extension-{{ $booking->id }}" style="display:none;">
                        <td colspan="8">

                            <div class="surface"
                                 style="padding:18px;margin:8px 0;border:1px solid #d1d5db;border-radius:12px;">

                                <h3 style="margin-bottom:8px;">
                                    Perpanjang Booking #{{ $booking->id }}
                                </h3>

                                <p style="color:#64748b;margin-bottom:15px;">
                                    {{ $booking->user?->name ?? '-' }}
                                    |
                                    {{ $booking->lapangan?->nama ?? '-' }}
                                    |
                                    Jam selesai saat ini:
                                    <strong>{{ substr($booking->end_time, 0, 5) }}</strong>
                                </p>

                                <form method="POST"
                                      action="{{ route('admin.booking-extensions.manual', $booking->id) }}"
                                      onsubmit="return confirm('Proses perpanjangan booking ini?')">

                                    @csrf

                                    <div class="grid-4">

                                        <div class="form-group">
                                            <label>Durasi Tambahan</label>
                                            <select class="select-ui"
                                                    name="hours"
                                                    required>
                                                @for($hour = 1; $hour <= 8; $hour++)
                                                    <option value="{{ $hour }}">
                                                        {{ $hour }} Jam
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Metode Pembayaran</label>
                                            <select class="select-ui"
                                                    name="payment_type"
                                                    required>
                                                <option value="Tunai">Tunai</option>
                                                <option value="Gratis">Gratis</option>
                                            </select>
                                        </div>

                                        <div class="form-group" style="grid-column:span 2;">
                                            <label>Catatan (Opsional)</label>
                                            <input class="input-ui"
                                                   type="text"
                                                   name="note"
                                                   maxlength="1000"
                                                   placeholder="Contoh: Perpanjangan atas permintaan pelanggan">
                                        </div>

                                    </div>

                                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:15px;">

                                        <button type="button"
                                                class="btn-ui"
                                                onclick="toggleExtension('extension-{{ $booking->id }}')">
                                            Batal
                                        </button>

                                        <button type="submit"
                                                class="btn-ui btn-green">
                                            Proses Perpanjangan
                                        </button>

                                    </div>

                                </form>

                            </div>

                        </td>
                    </tr>

                @endif


                {{-- RIWAYAT PERPANJANGAN --}}
                @if($booking->extensions && $booking->extensions->count() > 0)

                    <tr>
                        <td colspan="8">

                            <details style="padding:8px 12px;">
                                <summary style="cursor:pointer;font-weight:700;color:#166534;">
                                    Riwayat Perpanjangan
                                    ({{ $booking->extensions->count() }})
                                </summary>

                                <div style="overflow-x:auto;margin-top:12px;">

                                    <table style="width:100%;min-width:650px;">
                                        <thead>
                                            <tr>
                                                <th>Durasi</th>
                                                <th>Jam Sebelumnya</th>
                                                <th>Jam Baru</th>
                                                <th>Metode</th>
                                                <th>Biaya</th>
                                                <th>Status</th>
                                                <th>Tanggal Proses</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            @foreach($booking->extensions as $extension)

                                                <tr>
                                                    <td>
                                                        {{ $extension->hours }} jam
                                                    </td>

                                                    <td>
                                                        {{ substr($extension->previous_end_time, 0, 5) }}
                                                    </td>

                                                    <td>
                                                        {{ substr($extension->new_end_time, 0, 5) }}
                                                    </td>

                                                    <td>
                                                        {{ $extension->payment_method ?? '-' }}
                                                    </td>

                                                    <td>
                                                        Rp {{ number_format($extension->amount, 0, ',', '.') }}
                                                    </td>

                                                    <td>
                                                        @if($extension->status === 'Lunas')
                                                            <span style="color:#15803d;font-weight:700;">
                                                                Lunas
                                                            </span>
                                                        @elseif($extension->status === 'Menunggu')
                                                            <span style="color:#b45309;font-weight:700;">
                                                                Menunggu
                                                            </span>
                                                        @else
                                                            {{ $extension->status }}
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ $extension->processed_at?->format('d M Y H:i') ?? '-' }}
                                                    </td>
                                                </tr>

                                            @endforeach

                                        </tbody>
                                    </table>

                                </div>
                            </details>

                        </td>
                    </tr>

                @endif

            @empty

                <tr>
                    <td colspan="8" style="text-align:center;padding:25px;">
                        Belum ada data booking.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>
</div>


<script>
    function toggleExtension(id) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        element.style.display =
            element.style.display === 'none' ? 'table-row' : 'none';
    }
</script>

@endsection