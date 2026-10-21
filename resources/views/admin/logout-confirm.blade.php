@extends('layouts.admin', ['title' => 'Logout', 'heading' => 'Logout'])
@section('content')
<div class="center-box highlight">
    <h2>Yakin ingin keluar?</h2>
    <p class="muted">Sesi admin akan diakhiri.</p>
    <div class="btn-row" style="justify-content:center; margin-top:20px;">
        <a class="btn-ui btn-gray" href="{{ route('admin.dashboard') }}">Batal</a>
        <form method="POST" action="{{ route('logout.perform') }}">@csrf<button class="btn-ui btn-red">Logout</button></form>
    </div>
</div>
@endsection
