@extends('layouts.admin', ['title' => 'Edit Lapangan', 'heading' => 'Edit Lapangan'])
@section('content')
<form class="form-card wide-form" action="{{ route('admin.courts.update', $court) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('lapangan._form', [
        'court' => $court,
        'venues' => $venues ?? collect(),
        'venueAdmins' => $venueAdmins ?? collect(),
        'isSuperadmin' => $isSuperadmin ?? false,
    ])
</form>
@endsection
