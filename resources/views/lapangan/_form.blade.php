@php
    $isEdit = isset($court) && $court;
    $selectedVenueId = old('venue_id', $isEdit ? $court->venue_id : (($venues ?? collect())->first()?->id));
    $selectedOwner = old('owner_admin_id', $isEdit ? $court->owner_admin_id : null);
    $selectedStatus = old('operational_status', $isEdit ? ($court->operational_status ?: 'Beroperasi') : 'Beroperasi');
    $useVenueLocation = old('use_venue_location', $isEdit ? (bool) $court->use_venue_location : true);
    $selectedArea = old('area_type', $isEdit ? $court->area_type : '');
    $floorType = old('floor_type', $isEdit ? $court->floor_type : '');
    $courtFacilities = old('court_facilities', $isEdit ? ($court->court_facilities ?? []) : []);
    if (!is_array($courtFacilities)) { $courtFacilities = []; }
    $gallery = $isEdit ? collect($court->foto_gallery ?? [])->filter()->values() : collect();
    $priceRules = $isEdit ? $court->priceRules : collect();
    $oldDayTypes = old('price_day_types');
    $oldLabels = old('price_labels');
    $oldStarts = old('price_start_times');
    $oldEnds = old('price_end_times');
    $oldPrices = old('price_prices');
    $oldDays = old('price_days');
    $defaultCourtFacilities = ['Lampu penerangan','Net / jaring','Papan skor','Kursi penonton','Kotak P3K','Pendingin ruangan','Kipas angin'];
@endphp

<style>
    .court-form-section{margin:22px 0 6px;padding-top:18px;border-top:1px dashed #e2ebe5}
    .court-form-section h3{margin:0 0 4px;font-size:16px;color:#164b31;font-weight:800;display:flex;align-items:center;gap:8px}
    .court-form-section p{margin:0 0 12px;color:#7b8b81;font-size:12.5px}
    .price-rule-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;padding:12px;border:1px solid #e2eee6;border-radius:12px;background:#f7fbf8;margin-bottom:10px;align-items:end}
    .price-rule-row label{font-size:11px;font-weight:700;color:#4a6055;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.02em}
    .price-rule-row input,.price-rule-row select{width:100%;padding:9px;border:1px solid #d5e3da;border-radius:8px;background:#fff;font-size:13px}
    .price-days-picker{grid-column:1/-1;display:none;flex-wrap:wrap;gap:8px}
    .price-days-picker.show{display:flex}
    .price-days-picker label{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border:1px solid #d7e6dd;border-radius:8px;background:#fff;font-size:11.5px;text-transform:none;letter-spacing:0;cursor:pointer}
    .price-days-picker input{width:auto}
    .price-rule-remove{background:#fff2f2;border:1px solid #f3c9c9;color:#b23b3b;border-radius:8px;padding:9px;cursor:pointer;font-size:12px;font-weight:700}
    .court-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-top:10px}
    .court-gallery-item{position:relative;border:2px solid #e2eee6;border-radius:12px;overflow:hidden;background:#f4fbf6}
    .court-gallery-item img{width:100%;height:100px;object-fit:cover;display:block}
    .court-gallery-item .meta{padding:7px 8px;display:flex;flex-direction:column;gap:5px;font-size:11px;font-weight:700}
    .court-gallery-item .meta label{display:flex;align-items:center;gap:5px;cursor:pointer}
    .facility-checks{display:flex;flex-wrap:wrap;gap:8px}
    .facility-checks label{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border:1px solid #d7e6dd;border-radius:9px;background:#fff;font-size:12.5px;cursor:pointer}
</style>

{{-- ================= LOKASI & VENUE ================= --}}
<div class="court-form-section">
    <h3><i class="fa-solid fa-location-dot"></i> Venue &amp; Lokasi</h3>
    <p>Lapangan otomatis mengikuti lokasi venue kecuali kamu mengaktifkan lokasi khusus.</p>
</div>

<div class="grid-2">
    @if(($venues ?? collect())->count() > 1 || ($isSuperadmin ?? false))
        <div class="form-group">
            <label>Venue</label>
            <select class="select-ui" name="venue_id" required>
                <option value="">Pilih venue</option>
                @foreach($venues as $venue)
                    <option value="{{ $venue->id }}" @selected($selectedVenueId == $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
            <small>Pilih venue tempat lapangan ini berada.</small>
        </div>
    @else
        <input type="hidden" name="venue_id" value="{{ $selectedVenueId }}">
        <div class="form-group">
            <label>Venue</label>
            <input class="input-ui" value="{{ ($venues ?? collect())->first()?->name ?? 'Belum ada venue' }}" readonly>
            <small>Lapangan akan terhubung ke venue ini.</small>
        </div>
    @endif

    @if($isSuperadmin ?? false)
        <div class="form-group">
            <label>Pemilik Admin Venue (opsional)</label>
            <select class="select-ui" name="owner_admin_id">
                <option value="">Belum ditugaskan</option>
                @foreach(($venueAdmins ?? []) as $venueAdmin)
                    <option value="{{ $venueAdmin->id }}" @selected($selectedOwner == $venueAdmin->id)>{{ $venueAdmin->name }} ({{ $venueAdmin->email }})</option>
                @endforeach
            </select>
            <small>Kosongkan jika venue dikelola langsung superadmin.</small>
        </div>
    @endif
</div>

<label class="facility-checks" style="margin-bottom:12px">
    <input type="checkbox" name="use_venue_location" value="1" id="use-venue-location" @checked($useVenueLocation)>
    Gunakan lokasi venue untuk lapangan ini
</label>

<div class="grid-2" id="custom-location-fields" style="{{ $useVenueLocation ? 'display:none' : '' }}">
    <div class="form-group">
        <label>Alamat / Lokasi Khusus</label>
        <input class="input-ui" name="lokasi" id="lokasi-input" value="{{ old('lokasi', $isEdit ? $court->lokasi : '') }}" placeholder="Contoh: Gedung B lantai 2">
    </div>
    <div class="form-group">
        <label>Latitude</label>
        <input class="input-ui" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $isEdit ? $court->latitude : '') }}" placeholder="-6.3270">
    </div>
    <div class="form-group">
        <label>Longitude</label>
        <input class="input-ui" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $isEdit ? $court->longitude : '') }}" placeholder="108.3200">
    </div>
</div>

{{-- ================= IDENTITAS LAPANGAN ================= --}}
<div class="court-form-section">
    <h3><i class="fa-solid fa-vector-square"></i> Identitas Lapangan</h3>
    <p>Rating tidak diinput manual — nilai rating dihitung otomatis dari review pengguna.</p>
</div>

<div class="grid-2">
    <div class="form-group">
        <label>Nama Lapangan</label>
        <input class="input-ui" name="nama" value="{{ old('nama', $isEdit ? $court->nama : '') }}" placeholder="Contoh: Lapangan Futsal A">
    </div>
    <div class="form-group">
        <label>Jenis Olahraga</label>
        <select class="select-ui" name="jenis">
            @foreach(['Futsal','Badminton'] as $jenis)
                <option value="{{ $jenis }}" @selected(old('jenis', $isEdit ? $court->jenis : 'Futsal') === $jenis)>{{ $jenis }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Area</label>
        <select class="select-ui" name="area_type">
            <option value="">Pilih area</option>
            <option value="Indoor" @selected($selectedArea === 'Indoor')>Indoor</option>
            <option value="Outdoor" @selected($selectedArea === 'Outdoor')>Outdoor</option>
        </select>
    </div>
    <div class="form-group">
        <label>Jenis Lantai</label>
        <input class="input-ui" name="floor_type" value="{{ $floorType }}" placeholder="Contoh: Karpet sintetis, parket, vinyl">
    </div>
    <div class="form-group">
        <label>Harga Dasar per Jam</label>
        <input class="input-ui" name="harga" type="number" min="0" value="{{ old('harga', $isEdit ? $court->harga : '') }}" placeholder="150000">
        <small>Dipakai jika tidak ada aturan harga waktu khusus.</small>
    </div>
    <div class="form-group">
        <label>Status Operasional</label>
        <select class="select-ui" name="operational_status" id="operational-status">
            @foreach(['Beroperasi','Perawatan','Perbaikan','Tidak Aktif'] as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
            @endforeach
        </select>
        <small>Terpisah dari ketersediaan jadwal booking.</small>
    </div>
</div>

<div class="form-group" id="maintenance-note-wrap" style="{{ in_array($selectedStatus, ['Perawatan','Perbaikan'], true) ? '' : 'display:none' }}">
    <label>Informasi Perawatan / Kerusakan</label>
    <textarea class="textarea-ui" name="maintenance_note" rows="3" placeholder="Contoh: Rumput sintetis sedang diperbaiki.">{{ old('maintenance_note', $isEdit ? $court->maintenance_note : '') }}</textarea>
</div>

<div class="form-group">
    <label>Deskripsi</label>
    <textarea class="textarea-ui" name="deskripsi" rows="3" placeholder="Deskripsi singkat lapangan">{{ old('deskripsi', $isEdit ? $court->deskripsi : '') }}</textarea>
</div>

{{-- ================= FASILITAS LAPANGAN ================= --}}
<div class="court-form-section">
    <h3><i class="fa-solid fa-list-check"></i> Fasilitas Lapangan</h3>
    <p>Fasilitas khusus lapangan, berbeda dengan fasilitas umum venue.</p>
</div>

<div class="form-group">
    <div class="facility-checks">
        @foreach($defaultCourtFacilities as $facility)
            <label>
                <input type="checkbox" name="court_facilities[]" value="{{ $facility }}" @checked(in_array($facility, $courtFacilities, true))>
                {{ $facility }}
            </label>
        @endforeach
    </div>
    <input class="input-ui" name="court_facilities[]" style="margin-top:10px" placeholder="Tambah fasilitas lain (tekan Enter untuk menambah lagi)" value="">
    <small>Kamu juga bisa mengetik fasilitas tambahan pada kolom di atas.</small>
</div>

{{-- ================= HARGA BERBASIS WAKTU ================= --}}
<div class="court-form-section">
    <h3><i class="fa-solid fa-clock"></i> Aturan Harga Berbasis Waktu</h3>
    <p>Opsional. Contoh: weekday siang lebih murah, weekend dan malam lebih mahal. Jika kosong, harga dasar dipakai.</p>
</div>

<div id="price-rules">
    @if($oldPrices !== null)
        @foreach($oldPrices as $i => $price)
            <div class="price-rule-row">
                <div><label>Label</label><input name="price_labels[]" value="{{ $oldLabels[$i] ?? '' }}" placeholder="Weekday Siang"></div>
                <div>
                    <label>Tipe Hari</label>
                    <select name="price_day_types[]" class="price-day-type">
                        <option value="weekday" @selected(($oldDayTypes[$i] ?? 'weekday') === 'weekday')>Weekday</option>
                        <option value="weekend" @selected(($oldDayTypes[$i] ?? '') === 'weekend')>Weekend</option>
                        <option value="custom" @selected(($oldDayTypes[$i] ?? '') === 'custom')>Hari tertentu</option>
                    </select>
                </div>
                <div><label>Mulai</label><input type="time" name="price_start_times[]" value="{{ $oldStarts[$i] ?? '' }}"></div>
                <div><label>Selesai</label><input type="time" name="price_end_times[]" value="{{ $oldEnds[$i] ?? '' }}"></div>
                <div><label>Harga / jam</label><input type="number" name="price_prices[]" value="{{ $price }}" min="0"></div>
                <div><button type="button" class="price-rule-remove" onclick="this.closest('.price-rule-row').remove()">Hapus</button></div>
                <div class="price-days-picker {{ ($oldDayTypes[$i] ?? '') === 'custom' ? 'show' : '' }}">
                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                        <label><input type="checkbox" name="price_days[{{ $i }}][]" value="{{ $day }}" @checked(in_array($day, $oldDays[$i] ?? [], true))> {{ $day }}</label>
                    @endforeach
                </div>
            </div>
        @endforeach
    @elseif($isEdit && $priceRules->count())
        @foreach($priceRules as $i => $rule)
            <div class="price-rule-row">
                <div><label>Label</label><input name="price_labels[]" value="{{ $rule->label }}" placeholder="Weekday Siang"></div>
                <div>
                    <label>Tipe Hari</label>
                    <select name="price_day_types[]" class="price-day-type">
                        <option value="weekday" @selected($rule->day_type === 'weekday')>Weekday</option>
                        <option value="weekend" @selected($rule->day_type === 'weekend')>Weekend</option>
                        <option value="custom" @selected($rule->day_type === 'custom')>Hari tertentu</option>
                    </select>
                </div>
                <div><label>Mulai</label><input type="time" name="price_start_times[]" value="{{ substr((string) $rule->start_time, 0, 5) }}"></div>
                <div><label>Selesai</label><input type="time" name="price_end_times[]" value="{{ substr((string) $rule->end_time, 0, 5) }}"></div>
                <div><label>Harga / jam</label><input type="number" name="price_prices[]" value="{{ (int) $rule->price }}" min="0"></div>
                <div><button type="button" class="price-rule-remove" onclick="this.closest('.price-rule-row').remove()">Hapus</button></div>
                <div class="price-days-picker {{ $rule->day_type === 'custom' ? 'show' : '' }}">
                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                        <label><input type="checkbox" name="price_days[{{ $i }}][]" value="{{ $day }}" @checked(in_array($day, $rule->days ?? [], true))> {{ $day }}</label>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>

<button type="button" class="btn-ui btn-gray" id="add-price-rule" style="margin-bottom:8px">
    <i class="fa-solid fa-plus"></i> Tambah Aturan Harga
</button>

{{-- ================= FOTO ================= --}}
<div class="court-form-section">
    <h3><i class="fa-solid fa-images"></i> Galeri Foto Lapangan</h3>
    <p>Foto utama menjadi cover. Foto tambahan bisa menampilkan berbagai sudut lapangan.</p>
</div>

@if($isEdit && $gallery->count())
    <div class="court-gallery-grid">
        @foreach($gallery as $path)
            @php
                $isCover = $court->foto === $path;
                $url = str_starts_with($path, 'uploads/') ? asset($path) : asset('storage/' . ltrim($path, '/'));
            @endphp
            <div class="court-gallery-item" style="border-color:{{ $isCover ? '#12834e' : '#e2eee6' }}">
                <img src="{{ $url }}" alt="Foto lapangan">
                <div class="meta">
                    <label><input type="radio" name="cover_photo" value="{{ $path }}" @checked($isCover)> {{ $isCover ? 'Cover aktif' : 'Jadikan cover' }}</label>
                    <label style="color:#b23b3b"><input type="checkbox" name="remove_gallery[]" value="{{ $path }}"> Hapus foto</label>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="grid-2">
    <div class="form-group">
        <label>Foto Utama / Cover Baru</label>
        <input class="input-ui" name="foto" type="file" accept="image/*">
    </div>
    <div class="form-group">
        <label>Foto Galeri Tambahan</label>
        <input class="input-ui" name="foto_gallery[]" type="file" accept="image/*" multiple>
    </div>
</div>

<div style="display:flex; gap:10px; justify-content:flex-end;margin-top:18px">
    <a class="btn-ui btn-gray" href="{{ route('admin.courts') }}">Batal</a>
    <button class="btn-ui btn-green" type="submit">Simpan Lapangan</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const useVenue = document.getElementById('use-venue-location');
    const customFields = document.getElementById('custom-location-fields');
    const customInputs = customFields ? customFields.querySelectorAll('input') : [];

    function toggleLocation() {
        if (!useVenue || !customFields) return;
        const useVenueLocation = useVenue.checked;
        customFields.style.display = useVenueLocation ? 'none' : '';
        customInputs.forEach(function (input) {
            input.disabled = useVenueLocation;
        });
    }

    if (useVenue) {
        useVenue.addEventListener('change', toggleLocation);
        toggleLocation();
    }

    const status = document.getElementById('operational-status');
    const noteWrap = document.getElementById('maintenance-note-wrap');

    function toggleMaintenance() {
        if (!status || !noteWrap) return;
        noteWrap.style.display = ['Perawatan', 'Perbaikan'].includes(status.value) ? '' : 'none';
    }

    if (status) {
        status.addEventListener('change', toggleMaintenance);
        toggleMaintenance();
    }

    const rules = document.getElementById('price-rules');
    const addBtn = document.getElementById('add-price-rule');

    function bindRow(row) {
        const dayType = row.querySelector('.price-day-type');
        const picker = row.querySelector('.price-days-picker');

        if (!dayType || !picker) return;

        const update = function () {
            picker.classList.toggle('show', dayType.value === 'custom');
        };

        dayType.addEventListener('change', update);
        update();
    }

    rules?.querySelectorAll('.price-rule-row').forEach(bindRow);

    if (addBtn && rules) {
        addBtn.addEventListener('click', function () {
            const index = rules.querySelectorAll('.price-rule-row').length;
            const row = document.createElement('div');
            row.className = 'price-rule-row';
            row.innerHTML = `
                <div><label>Label</label><input name="price_labels[]" placeholder="Weekend Malam"></div>
                <div>
                    <label>Tipe Hari</label>
                    <select name="price_day_types[]" class="price-day-type">
                        <option value="weekday">Weekday</option>
                        <option value="weekend">Weekend</option>
                        <option value="custom">Hari tertentu</option>
                    </select>
                </div>
                <div><label>Mulai</label><input type="time" name="price_start_times[]"></div>
                <div><label>Selesai</label><input type="time" name="price_end_times[]"></div>
                <div><label>Harga / jam</label><input type="number" name="price_prices[]" min="0"></div>
                <div><button type="button" class="price-rule-remove">Hapus</button></div>
                <div class="price-days-picker">
                    ${['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'].map(day => `<label><input type="checkbox" name="price_days[${index}][]" value="${day}"> ${day}</label>`).join('')}
                </div>
            `;
            rules.appendChild(row);
            bindRow(row);
            row.querySelector('.price-rule-remove').addEventListener('click', function () {
                row.remove();
            });
        });
    }
});
</script>
