@extends('layouts.admin', ['title' => 'Tambah Lapangan', 'heading' => 'Tambah Lapangan'])
@section('content')
<form class="form-card wide-form" action="{{ route('admin.courts.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('lapangan._form', [
        'court' => null,
        'venues' => $venues ?? collect(),
        'venueAdmins' => $venueAdmins ?? collect(),
        'isSuperadmin' => $isSuperadmin ?? false,
    ])
</form>
@endsection
