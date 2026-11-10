@extends('layouts.admin', ['title' => 'Konfirmasi Pengajuan Venue', 'heading' => 'Konfirmasi Pengajuan Venue'])

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu',
        'revision' => 'Perlu Revisi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'Menunggu' => 'Menunggu',
        'Perlu Revisi' => 'Perlu Revisi',
    ];
    $statusStyles = [
        'pending' => 'waiting', 'Menunggu' => 'waiting',
        'revision' => 'revision', 'Perlu Revisi' => 'revision',
        'approved' => 'approved', 'rejected' => 'rejected',
    ];
    $currentStatus = request('status', '');
    $statusCounts = [
        'all' => $applications->total(),
        'pending' => $applications->getCollection()->whereIn('status', ['pending', 'Menunggu'])->count(),
        'revision' => $applications->getCollection()->whereIn('status', ['revision', 'Perlu Revisi'])->count(),
        'approved' => $applications->getCollection()->where('status', 'approved')->count(),
        'rejected' => $applications->getCollection()->where('status', 'rejected')->count(),
    ];
@endphp

<style>
.va-page{display:grid;gap:18px;color:#17231f}
.va-hero{position:relative;overflow:hidden;background:linear-gradient(120deg,#102d25,#17634a);color:#fff;border-radius:20px;padding:25px 28px;display:flex;justify-content:space-between;align-items:center;gap:18px}
.va-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid #ffffff20;border-radius:50%;right:7%;top:-145px;box-shadow:0 0 0 28px #ffffff08,0 0 0 58px #ffffff06}
.va-hero-copy,.va-hero-stat{position:relative;z-index:1}
.va-hero h2{margin:0 0 7px;font-size:22px;color:#fff}.va-hero p{margin:0;color:#d2e8dd;font-size:13px;max-width:620px;line-height:1.6}
.va-hero-stat{text-align:right;min-width:110px}.va-hero-stat strong{display:block;font-size:34px;line-height:1.1}.va-hero-stat span{font-size:11px;color:#d2e8dd}
.va-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.va-summary-card{background:#fff;border:1px solid #e7ece9;border-radius:15px;padding:15px 17px;display:flex;align-items:center;gap:12px;min-width:0}
.va-summary-icon{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#eef7f1;color:#17634a;font-size:16px;flex:none}
.va-summary-card strong{display:block;font-size:21px;line-height:1.2}.va-summary-card span{display:block;color:#718078;font-size:11px;margin-top:3px}
.va-toolbar{background:#fff;border:1px solid #e7ece9;border-radius:15px;padding:15px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.va-toolbar form{display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%}
.va-control{height:41px;border:1px solid #dce5df;border-radius:10px;padding:0 12px;background:#fff;color:#263a31;font:inherit;font-size:12px;min-width:155px}
.va-control.search{flex:1;min-width:220px}.va-btn{border:0;border-radius:10px;padding:11px 15px;background:#17634a;color:#fff;font-weight:700;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer}
.va-btn.secondary{background:#f1f5f2;color:#40564a}.va-btn.outline{background:#fff;color:#52645a;border:1px solid #dce5df}.va-btn.danger{background:#fff1f0;color:#b42318}.va-btn:focus,.va-control:focus{outline:3px solid #17634a20;outline-offset:1px}
.va-list{display:grid;gap:14px}
.va-card{background:#fff;border:1px solid #e6ece8;border-radius:17px;overflow:hidden;box-shadow:0 3px 12px #17231f06}
.va-card-head{padding:18px 20px;border-bottom:1px solid #edf1ee;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}
.va-card-title{display:flex;gap:12px;align-items:flex-start;min-width:0}.va-store-icon{width:43px;height:43px;display:grid;place-items:center;background:#edf7f1;color:#17634a;border-radius:13px;flex:none;font-size:17px}
.va-card-title h3{margin:0 0 5px;font-size:16px;color:#17231f}.va-card-title p{margin:0;color:#78867e;font-size:11px;line-height:1.5;overflow-wrap:anywhere}
.va-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:99px;font-size:10px;font-weight:800;white-space:nowrap}
.va-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.va-pill.waiting{background:#fff7e6;color:#a15c00}.va-pill.revision{background:#f2edff;color:#6941c6}.va-pill.approved{background:#eaf7ef;color:#16804a}.va-pill.rejected{background:#fff0ef;color:#c43226}
.va-card-body{padding:18px 20px}.va-section-label{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#8a9890;margin:0 0 10px}
.va-data-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}
.va-data{background:#f7f9f8;border:1px solid #eff2f0;border-radius:11px;padding:11px 12px;min-width:0;overflow-wrap:anywhere}
.va-data small{display:block;color:#839087;font-size:10px;margin-bottom:5px}.va-data strong,.va-data span{font-size:12px;font-weight:600;color:#34483d;line-height:1.5}
.va-data.wide{grid-column:1/-1}.va-docs{display:flex;gap:8px;flex-wrap:wrap}
.va-doc{display:inline-flex;align-items:center;gap:7px;padding:9px 11px;background:#eef7f1;border:1px solid #dcefe3;border-radius:9px;color:#17634a;text-decoration:none;font-size:11px;font-weight:700}
.va-empty-doc{padding:11px 13px;background:#fff8e9;color:#946200;border-radius:10px;font-size:11px}
.va-history{margin-top:17px;padding:13px 15px;background:#f8faf9;border-radius:11px}.va-history summary{font-size:11px;font-weight:800;color:#53645a;cursor:pointer}.va-history ul{margin:10px 0 0;padding-left:17px;color:#718078;font-size:11px;line-height:1.8}
.va-actions{margin-top:18px;padding-top:16px;border-top:1px solid #edf1ee;display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.va-actions form{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.va-note{height:38px;min-width:190px;border:1px solid #dce5df;border-radius:9px;padding:0 11px;font:inherit;font-size:11px}
.va-note-info{margin:14px 0 0;padding:11px 13px;background:#f7f9f8;border-radius:10px;color:#68776e;font-size:11px;line-height:1.6}
.va-empty{padding:45px 20px;text-align:center;background:#fff;border:1px dashed #d9e3dc;border-radius:16px;color:#77857c}.va-empty i{font-size:27px;color:#a5b3aa;margin-bottom:10px}.va-empty strong{display:block;color:#34483d;margin-bottom:5px}
@media(max-width:900px){.va-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.va-data-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.va-hero{padding:20px;align-items:flex-start}.va-hero h2{font-size:18px}.va-hero-stat strong{font-size:27px}.va-summary{gap:8px}.va-summary-card{padding:11px;gap:8px}.va-summary-icon{width:34px;height:34px}.va-summary-card strong{font-size:18px}.va-toolbar form{display:grid;grid-template-columns:1fr 1fr}.va-control.search{grid-column:1/-1;min-width:0}.va-card-head{padding:15px;flex-direction:column}.va-card-body{padding:15px}.va-data-grid{grid-template-columns:1fr}.va-data.wide{grid-column:auto}.va-actions,.va-actions form{width:100%}.va-actions form .va-note{flex:1;min-width:0}.va-actions .va-btn{flex:1}}
</style>

<div class="va-page">
    <section class="va-hero">
        <div class="va-hero-copy">
            <h2>Verifikasi Mitra Venue</h2>
            <p>Tinjau identitas pemilik, informasi usaha, lokasi, dan dokumen pendukung sebelum memberikan akses Admin Venue.</p>
        </div>
        <div class="va-hero-stat">
            <strong>{{ $applications->total() }}</strong>
            <span>Total pengajuan</span>
        </div>
    </section>

    <section class="va-summary">
        <div class="va-summary-card"><div class="va-summary-icon"><i class="fa-regular fa-clock"></i></div><div><strong>{{ $statusCounts['pending'] }}</strong><span>Menunggu · halaman ini</span></div></div>
        <div class="va-summary-card"><div class="va-summary-icon"><i class="fa-solid fa-rotate"></i></div><div><strong>{{ $statusCounts['revision'] }}</strong><span>Perlu revisi · halaman ini</span></div></div>
        <div class="va-summary-card"><div class="va-summary-icon"><i class="fa-solid fa-circle-check"></i></div><div><strong>{{ $statusCounts['approved'] }}</strong><span>Disetujui · halaman ini</span></div></div>
        <div class="va-summary-card"><div class="va-summary-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><strong>{{ $statusCounts['rejected'] }}</strong><span>Ditolak · halaman ini</span></div></div>
    </section>

    <section class="va-toolbar">
        <form method="GET" action="{{ route('admin.venue-applications') }}">
            <input class="va-control search" name="q" value="{{ request('q') }}" placeholder="Cari nama venue, pemilik, atau perusahaan...">
            <select class="va-control" name="status">
                <option value="">Semua status</option>
                @foreach(['pending'=>'Menunggu','revision'=>'Perlu Revisi','approved'=>'Disetujui','rejected'=>'Ditolak'] as $key=>$label)
                    <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="va-btn" type="submit"><i class="fa-solid fa-filter"></i> Terapkan</button>
            <a class="va-btn secondary" href="{{ route('admin.venue-applications') }}">Reset</a>
        </form>
    </section>

    <section class="va-list">
        @forelse($applications as $app)
            @php
                $label = $statusLabels[$app->status] ?? $app->status;
                $style = $statusStyles[$app->status] ?? 'waiting';
                $sports = is_array($app->sports) ? $app->sports : (is_string($app->sports) ? (json_decode($app->sports, true) ?: []) : []);
            @endphp
            <article class="va-card">
                <header class="va-card-head">
                    <div class="va-card-title">
                        <div class="va-store-icon"><i class="fa-solid fa-store"></i></div>
                        <div>
                            <h3>{{ $app->venue_name }}</h3>
                            <p><i class="fa-regular fa-user"></i> {{ $app->user?->name ?? 'Pemohon tidak tersedia' }} · {{ $app->user?->email ?? '-' }}</p>
                            <p><i class="fa-regular fa-calendar"></i> Diajukan {{ $app->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                        </div>
                    </div>
                    <span class="va-pill {{ $style }}">{{ $label }}</span>
                </header>

                <div class="va-card-body">
                    <p class="va-section-label">Informasi pemohon & perusahaan</p>
                    <div class="va-data-grid">
                        <div class="va-data"><small>Perusahaan / badan usaha</small><strong>{{ $app->company_name ?: '-' }}</strong><br><span>{{ $app->business_type ?: '-' }}</span></div>
                        <div class="va-data"><small>NIB</small><strong>{{ $app->nib ?: '-' }}</strong></div>
                        <div class="va-data"><small>Nomor identitas pemilik</small><strong>{{ $app->identity_number ?: '-' }}</strong></div>
                        <div class="va-data"><small>Alamat pemilik</small><span>{{ $app->owner_address ?: '-' }}</span></div>
                        <div class="va-data"><small>Alamat perusahaan</small><span>{{ $app->company_address ?: '-' }}</span></div>
                        <div class="va-data"><small>Kota / Provinsi</small><span>{{ $app->city ?: '-' }} / {{ $app->province ?: '-' }}</span></div>
                        <div class="va-data"><small>Telepon perusahaan</small><strong>{{ $app->company_phone ?: '-' }}</strong></div>
                        <div class="va-data"><small>WhatsApp pemohon</small><strong>{{ $app->applicant_phone ?: '-' }}</strong></div>
                        <div class="va-data"><small>Status kepemilikan</small><strong>{{ $app->ownership_type ?: 'Belum dicatat' }}</strong></div>
                        <div class="va-data wide"><small>Deskripsi perusahaan</small><span>{{ $app->company_description ?: '-' }}</span></div>
                    </div>

                    <p class="va-section-label" style="margin-top:18px">Informasi venue</p>
                    <div class="va-data-grid">
                        <div class="va-data"><small>Alamat venue</small><span>{{ $app->address ?: '-' }}</span></div>
                        <div class="va-data"><small>Kontak venue</small><strong>{{ $app->contact_phone ?: '-' }}</strong></div>
                        <div class="va-data"><small>Jam operasional</small><strong>{{ $app->operating_hours ?: '-' }}</strong></div>
                        <div class="va-data"><small>Jenis olahraga</small><span>{{ count($sports) ? implode(', ', $sports) : '-' }}</span></div>
                        <div class="va-data"><small>Lokasi Google Maps</small>
                            @if($app->google_maps_url)
                                <a href="{{ $app->google_maps_url }}" target="_blank" rel="noopener" class="va-doc"><i class="fa-solid fa-map-location-dot"></i> Buka lokasi</a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                        <div class="va-data wide"><small>Deskripsi venue & fasilitas</small><span>{{ $app->description ?: '-' }}</span></div>
                    </div>

                    <div style="margin-top:18px">
                        <p class="va-section-label">Dokumen dan foto pendukung</p>
                        @if($app->documents->isNotEmpty())
                            <div class="va-docs">
                                @foreach($app->documents as $document)
                                    <span style="display:inline-flex;gap:6px;flex-wrap:wrap">
                                        <a class="va-doc" href="{{ route('admin.venue-applications.documents.preview', [$app, $document]) }}" target="_blank" rel="noopener"><i class="fa-regular fa-eye"></i> Preview · {{ $document->document_type }}</a>
                                        <a class="va-doc" href="{{ route('admin.venue-applications.documents.download', [$app, $document]) }}"><i class="fa-solid fa-download"></i> Unduh</a>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="va-empty-doc"><i class="fa-solid fa-triangle-exclamation"></i> Belum ada dokumen digital pada pengajuan ini.</div>
                        @endif
                    </div>

                    @if($app->histories->isNotEmpty())
                        <details class="va-history">
                            <summary><i class="fa-solid fa-clock-rotate-left"></i> Riwayat status ({{ $app->histories->count() }})</summary>
                            <ul>
                                @foreach($app->histories as $history)
                                    <li>{{ $history->created_at?->format('d-m-Y H:i') }} · {{ $history->from_status ?: 'Baru' }} → {{ $history->to_status }} · {{ $history->note ?: '-' }} · {{ $history->actor?->name ?? 'Sistem' }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    @if(in_array($app->status, ['pending', 'revision', 'Menunggu', 'Perlu Revisi'], true))
                        <div class="va-actions">
                            <form method="POST" action="{{ route('admin.venue-applications.approve', $app) }}">
                                @csrf
                                <button type="submit" class="va-btn" onclick="return confirm('Setujui pengajuan venue {{ addslashes($app->venue_name) }}?')"><i class="fa-solid fa-check"></i> Setujui pengajuan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.venue-applications.revision', $app) }}">
                                @csrf
                                <input class="va-note" name="admin_note" placeholder="Tuliskan catatan revisi..." maxlength="1000" required>
                                <button type="submit" class="va-btn secondary"><i class="fa-solid fa-rotate"></i> Minta revisi</button>
                            </form>
                            <form method="POST" action="{{ route('admin.venue-applications.reject', $app) }}">
                                @csrf
                                <input class="va-note" name="admin_note" placeholder="Alasan penolakan..." maxlength="1000" required>
                                <button type="submit" class="va-btn danger" onclick="return confirm('Tolak pengajuan venue ini?')"><i class="fa-solid fa-xmark"></i> Tolak</button>
                            </form>
                        </div>
                    @elseif($app->admin_note)
                        <div class="va-note-info"><strong>Catatan Superadmin:</strong> {{ $app->admin_note }}</div>
                    @endif
                </div>
            </article>
        @empty
            <div class="va-empty"><i class="fa-regular fa-folder-open"></i><strong>Tidak ada pengajuan venue</strong>Belum ada data yang cocok dengan pencarian atau filter ini.</div>
        @endforelse
    </section>

    @if(method_exists($applications, 'links'))
        <div>{{ $applications->links() }}</div>
    @endif
</div>
@endsection
