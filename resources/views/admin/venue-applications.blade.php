@extends('layouts.admin', ['title' => 'Request Admin Venue', 'heading' => 'Request Admin Venue'])

@section('content')
<style>
    .va-admin-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:18px;margin-bottom:16px}
    .va-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .va-admin-item{background:#f8fafc;border-radius:10px;padding:12px;overflow-wrap:anywhere}
    .va-admin-item small{display:block;color:#64748b;font-size:11px;margin-bottom:5px}
    .va-doc-list{display:flex;gap:8px;flex-wrap:wrap}
    .va-doc-link{display:inline-flex;padding:8px 11px;border-radius:8px;background:#eaf6f1;color:#12634f;text-decoration:none;font-size:12px;font-weight:700}
    .va-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .va-actions input{max-width:230px;padding:9px;border:1px solid #d5dee7;border-radius:8px}
    .va-status{font-weight:800}
    @media(max-width:650px){.va-admin-grid{grid-template-columns:1fr}}
</style>

<div class="panel-card">
    <div class="panel-title">Daftar Pengajuan Venue</div>
    <form method="GET" action="{{ route('admin.venue-applications') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 20px">
        <input name="q" value="{{ request('q') }}" placeholder="Cari pemilik, perusahaan, atau venue" style="min-width:240px;padding:10px;border:1px solid #d5dee7;border-radius:8px">
        <select name="status" style="padding:10px;border:1px solid #d5dee7;border-radius:8px"><option value="">Semua status</option>@foreach(['pending'=>'Menunggu','revision'=>'Perlu Revisi','approved'=>'Disetujui','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
        <button class="btn-ui btn-green">Terapkan</button><a class="btn-ui" href="{{ route('admin.venue-applications') }}">Reset</a>
    </form>

    @forelse($applications as $app)
        <article class="va-admin-card">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
                <div>
                    <h3 style="margin:0 0 5px">{{ $app->venue_name }}</h3>
                    <div style="color:#64748b;font-size:12px">
                        Pemohon: {{ $app->user?->name }} · {{ $app->user?->email }}
                    </div>
                </div>
                <span class="va-status">{{ ['pending'=>'Menunggu','revision'=>'Perlu Revisi','approved'=>'Disetujui','rejected'=>'Ditolak'][$app->status] ?? $app->status }}</span>
            </div>

            <div class="va-admin-grid">
                <div class="va-admin-item"><small>Perusahaan / badan usaha</small>{{ $app->company_name ?: '-' }} · {{ $app->business_type ?: '-' }}</div>
                <div class="va-admin-item"><small>NIB / identitas pemilik</small>{{ $app->nib ?: '-' }} · {{ $app->identity_number ?: '-' }}</div>
                <div class="va-admin-item"><small>Alamat pemilik</small>{{ $app->owner_address ?: '-' }}</div>
                <div class="va-admin-item"><small>Alamat perusahaan</small>{{ $app->company_address ?: '-' }} · {{ $app->city ?: '-' }}, {{ $app->province ?: '-' }}</div>
                <div class="va-admin-item"><small>Telepon perusahaan</small>{{ $app->company_phone ?: '-' }}</div>
                <div class="va-admin-item"><small>Deskripsi perusahaan</small>{{ $app->company_description ?: '-' }}</div>
                <div class="va-admin-item">
                    <small>Alamat venue</small>
                    {{ $app->address }}
                </div>
                <div class="va-admin-item">
                    <small>WhatsApp pemohon</small>
                    {{ $app->applicant_phone ?: '-' }}
                </div>
                <div class="va-admin-item">
                    <small>Kontak venue</small>
                    {{ $app->contact_phone }}
                </div>
                <div class="va-admin-item">
                    <small>Jenis olahraga</small>
                    {{ implode(', ', $app->sports ?? []) }}
                </div>
                <div class="va-admin-item">
                    <small>Status kepemilikan</small>
                    {{ $app->ownership_type ?: 'Belum dicatat' }}
                </div>
                <div class="va-admin-item">
                    <small>Jam operasional</small>
                    {{ $app->operating_hours ?: '-' }}
                </div>
                <div class="va-admin-item">
                    <small>Lokasi Google Maps</small>
                    @if($app->google_maps_url)
                        <a href="{{ $app->google_maps_url }}" target="_blank" rel="noopener">Buka lokasi</a>
                    @else
                        -
                    @endif
                </div>
                <div class="va-admin-item" style="grid-column:1/-1">
                    <small>Deskripsi venue dan fasilitas</small>
                    {{ $app->description ?: '-' }}
                </div>
            </div>

            <div style="margin-top:16px">
                <strong style="display:block;margin-bottom:9px">Dokumen dan foto pendukung</strong>
                @if($app->documents->isNotEmpty())
                    <div class="va-doc-list">
                        @foreach($app->documents as $document)
                            <span style="display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap">
                                <a class="va-doc-link" href="{{ route('admin.venue-applications.documents.preview', [$app, $document]) }}" target="_blank" rel="noopener">Preview · {{ $document->document_type }}</a>
                                <a class="va-doc-link" href="{{ route('admin.venue-applications.documents.download', [$app, $document]) }}">Unduh</a>
                            </span>
                        @endforeach
                    </div>
                @else
                    <span style="color:#b45309;font-size:13px">Belum ada dokumen digital pada pengajuan ini.</span>
                @endif
            </div>

            @if($app->histories->isNotEmpty())
                <div style="margin-top:14px;color:#64748b;font-size:12px"><strong>Riwayat status</strong><ul>@foreach($app->histories as $history)<li>{{ $history->created_at?->format('d-m-Y H:i') }} · {{ $history->from_status ?: 'baru' }} → {{ $history->to_status }} · {{ $history->note }} · {{ $history->actor?->name ?? 'Sistem' }}</li>@endforeach</ul></div>
            @endif

            @if(in_array($app->status, ['pending', 'revision', 'Menunggu', 'Perlu Revisi'], true))
                <div class="va-actions" style="margin-top:18px">
                    <form method="POST" action="{{ route('admin.venue-applications.approve', $app) }}">
                        @csrf
                        <button type="submit" class="btn-ui btn-green"
                                onclick="return confirm('Setujui pengajuan venue ini?')">
                            Setujui Pengajuan
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.venue-applications.revision', $app) }}" style="display:flex;gap:8px;flex-wrap:wrap">
                        @csrf
                        <input name="admin_note" placeholder="Catatan revisi" maxlength="1000" required>
                        <button type="submit" class="btn-ui">Minta Revisi</button>
                    </form>
                    <form method="POST" action="{{ route('admin.venue-applications.reject', $app) }}" style="display:flex;gap:8px;flex-wrap:wrap">
                        @csrf
                        <input name="admin_note" placeholder="Alasan penolakan" maxlength="1000" required>
                        <button type="submit" class="btn-ui" onclick="return confirm('Tolak pengajuan venue ini?')">Tolak Pengajuan</button>
                    </form>
                </div>
            @elseif($app->admin_note)
                <p style="margin:14px 0 0;color:#64748b;font-size:13px">
                    <strong>Catatan Superadmin:</strong> {{ $app->admin_note }}
                </p>
            @endif
        </article>
    @empty
        <p>Belum ada pengajuan venue.</p>
    @endforelse
    @if(method_exists($applications, 'links'))
        <div style="margin-top:18px">{{ $applications->links() }}</div>
    @endif
</div>
@endsection
