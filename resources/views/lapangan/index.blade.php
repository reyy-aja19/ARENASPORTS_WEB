<!DOCTYPE html>
<html>
<head>
    <title>Data Lapangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Data Lapangan</h2>

    <a href="/lapangan/create" class="btn btn-primary mb-3">Tambah</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <tr>
            <th>Nama</th>
            <th>Jenis</th>
            <th>Harga</th>
            <th>Aksi</th>
        </tr>

        @foreach($lapangan as $l)
        <tr>
            <td>{{ $l->nama }}</td>
            <td>{{ $l->jenis }}</td>
            <td>{{ $l->harga }}</td>
            <td>
                <a href="/lapangan/{{ $l->id_lapangan }}/edit" class="btn btn-warning btn-sm">Edit</a>

                <form action="/lapangan/{{ $l->id_lapangan }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </td>
        </tr>
        @endforeach

    </table>
</div>

</body>
</html>
