<?php
session_start();
include('includes/config.php');

/* ===============================
   CEK LOGIN USER (FINAL & AMAN)
================================*/
$logged_in = isset($_SESSION['logged_in'], $_SESSION['role'], $_SESSION['id_user'])
             && $_SESSION['logged_in'] === true
             && $_SESSION['role'] === 'user';

$user_nama = $logged_in ? ($_SESSION['nama'] ?? '') : '';
$id_user   = $logged_in ? ($_SESSION['id_user'] ?? 0) : 0;

$foto_nav = ($logged_in && !empty($_SESSION['foto']))
    ? 'uploads/profile/' . $_SESSION['foto']
    : 'assets/image/default-profile.png';



// ===== FILTER LAPANGAN =====
$filter_nama = $_GET['nama'] ?? "";
$filter_lokasi = $_GET['lokasi'] ?? "";
$filter_cabor = $_GET['cabor'] ?? "";
$filter_sort = $_GET['filter'] ?? "";

// Ambil daftar opsi dropdown
$venue_list = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT nama FROM lapangan ORDER BY nama ASC"), MYSQLI_ASSOC);
$lokasi_list = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT lokasi FROM lapangan ORDER BY lokasi ASC"), MYSQLI_ASSOC);
$cabor_list = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT kategori FROM lapangan ORDER BY kategori ASC"), MYSQLI_ASSOC);

// Query lapangan
$query = "SELECT l.*, p.nama AS pengelola 
          FROM lapangan l
          LEFT JOIN pengelola p ON l.id_pengelola = p.id_pengelola
          WHERE 1=1";

if ($filter_nama !== "")
    $query .= " AND l.nama='" . mysqli_real_escape_string($conn, $filter_nama) . "'";
if ($filter_lokasi !== "")
    $query .= " AND l.lokasi='" . mysqli_real_escape_string($conn, $filter_lokasi) . "'";
if ($filter_cabor !== "")
    $query .= " AND l.kategori='" . mysqli_real_escape_string($conn, $filter_cabor) . "'";

// Sorting
// Filter & Sorting
if ($filter_sort == "harga_asc") {
    $query .= " ORDER BY l.harga_per_jam ASC";
} else if ($filter_sort == "harga_desc") {
    $query .= " ORDER BY l.harga_per_jam DESC";
} else if ($filter_sort == "tersedia") {
    $query .= " AND l.status = 'Tersedia'";
} else if ($filter_sort == "tidak_tersedia") {
    $query .= " AND l.status = 'Tidak tersedia'";
} else if ($filter_sort == "az") {
    $query .= " ORDER BY l.nama ASC";
} else if ($filter_sort == "za") {
    $query .= " ORDER BY l.nama DESC";
}


$lapangan = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda | Sport Field Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* --- GLOBAL --- */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        footer {
            margin-top: auto;
        }

        /* --- CARD --- */
        .card {
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* --- FILTER INPUTS --- */
        .filter-input {
            border-radius: 10px;
            height: 45px !important;
            /* konsistensi */
        }

        /* --- FILTER BUTTON (ikon slider) --- */
        .filter-btn,
        .dropdown .btn.filter-input {
            height: 45px !important;
            padding: 6px 10px !important;
            border-radius: 10px !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-btn i,
        .dropdown .btn.filter-input i {
            font-size: 18px;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .filter-input {
                height: 48px !important;
                /* sedikit lebih besar di mobile */
            }

            .dropdown .btn.filter-input {
                height: 48px !important;
            }
        }
        /* Dropdown filter agar tidak sempit */
.filter-dropdown-menu {
    min-width: 180px !important;   /* ukuran ideal agar teks tidak keluar */
    white-space: normal !important; /* biar teks bisa turun baris */
    word-break: break-word;        /* jaga kalau ada teks panjang */
    padding: 8px 0;
}

.input-group-text {
    border-radius: 10px 0 0 10px !important;
}

.input-group .form-select {
    border-radius: 0 10px 10px 0 !important;
}

.input-group-text i {
    font-size: 16px;
    color: #444;
}


    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-success fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <img src="assets/image/logo-putih.png" width="40" class="me-2"> Sport Field Rental
            </a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link text-white" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="user/openmatch.php">Open Match</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="user/poin.php">Poin</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="user/riwayat.php">Riwayat</a></li>

                    <?php if ($logged_in): ?>
                        <li class="nav-item dropdown ms-3 position-relative">
                            <a class="nav-link d-flex align-items-center text-white" href="#" id="notifDropdown"
                                role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-4"></i>
                                <span id="notifBadge" class="badge bg-danger badge-notif" style="display:none;">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-2" id="notifList" style="min-width:250px;">
                                <li class="text-center">Memuat notifikasi...</li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#"
                                id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= $foto_nav ?>" class="rounded-circle border border-white" width="38"
                                    height="38" style="object-fit:cover;">
                                <span class="ms-2"><?= htmlspecialchars($user_nama) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="user/akun.php">Profil Saya</a></li>
                                <li>
    <a class="dropdown-item" href="logout.php"
       onclick="return confirm('Apakah Anda yakin ingin keluar?')">
        Logout
    </a>
</li>

                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item d-flex align-items-center ms-3">
                            <a href="login.php" class="d-flex align-items-center text-white text-decoration-none">
                                <img src="assets/image/default-profile.png" class="rounded-circle border border-white"
                                    width="38" height="38">
                                <span class="ms-2 fw-bold">Login / Register</span>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>

    <!-- CONTAINER -->
    <div class="container" style="margin-top:90px; margin-bottom:100px;">
        <h3 class="text-center mb-4 fw-bold text-success">Daftar Lapangan</h3>

        <!-- FILTER FORM -->
        <form method="GET" class="row g-2 mb-4 justify-content-center align-items-center">

            <!-- Nama Venue -->
           <div class="col-12 col-md-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search"></i>
        </span>
        <select name="nama" class="form-select filter-input border-start-0">
            <option value="">Cari Lapangan</option>
            <?php foreach ($venue_list as $v): ?>
                <option value="<?= htmlspecialchars($v['nama']); ?>" 
                    <?= ($filter_nama == $v['nama']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($v['nama']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

            <!-- Lokasi -->
           <div class="col-12 col-md-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-geo-alt"></i>
        </span>
        <select name="lokasi" class="form-select filter-input border-start-0">
            <option value="">Pilih Lokasi</option>
            <?php foreach ($lokasi_list as $l): ?>
                <option value="<?= htmlspecialchars($l['lokasi']); ?>" 
                    <?= ($filter_lokasi == $l['lokasi']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($l['lokasi']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>


            <!-- Cabor -->
            <div class="col-12 col-md-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-key"></i>
        </span>
        <select name="cabor" class="form-select filter-input border-start-0">
            <option value="">Pilih Cabang Olahraga</option>
            <?php foreach ($cabor_list as $c): ?>
                <option value="<?= htmlspecialchars($c['kategori']); ?>" 
                    <?= ($filter_cabor == $c['kategori']) ? 'selected' : ''; ?>>
                    <?= ucfirst($c['kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

            <!-- Tombol Filter Ikon -->
          <div class="col-3 col-md-1 d-flex align-items-center justify-content-center">
    <div class="dropdown w-100">
        <button class="btn btn-light filter-input px-2 py-2 w-100 d-flex justify-content-center align-items-center"
            type="button" data-bs-toggle="dropdown">
            <i class="bi bi-sliders"></i>
        </button>

        <ul class="dropdown-menu filter-dropdown-menu">
            <li><a class="dropdown-item" href="?filter=harga_asc&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Harga Terendah</a></li>
            <li><a class="dropdown-item" href="?filter=harga_desc&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Harga Tertinggi</a></li>
            <li><a class="dropdown-item" href="?filter=tersedia&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Status: Tersedia</a></li>
            <li><a class="dropdown-item" href="?filter=tidak_tersedia&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Status: Tidak Tersedia</a></li>
            <li><a class="dropdown-item" href="?filter=az&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Nama A - Z</a></li>
            <li><a class="dropdown-item" href="?filter=za&nama=<?= $filter_nama ?>&lokasi=<?= $filter_lokasi ?>&cabor=<?= $filter_cabor ?>">Nama Z - A</a></li>
        </ul>
    </div>
</div>


            <!-- Tombol Cari -->
            <div class="col-9 col-md-2 d-flex align-items-center">
                <button type="submit" class="btn btn-success w-100">Cari Venue</button>
            </div>

        </form>


        <div class="row g-4">
            <?php
            if (!$lapangan) {
                echo "<div class='alert alert-danger'>Query error: " . mysqli_error($conn) . "</div>";
            } else if (mysqli_num_rows($lapangan) > 0) {

                while ($l = mysqli_fetch_assoc($lapangan)) {
                    ?>
                        <div class="col-md-4 d-flex">
                            <div class="card shadow-sm border-0 w-100 lapangan-card"
     data-nama="<?= htmlspecialchars($l['nama']); ?>"
     data-lokasi="<?= htmlspecialchars($l['lokasi']); ?>"
     data-harga="<?= number_format($l['harga_per_jam'], 0, ",", "."); ?>"
     data-status="<?= htmlspecialchars($l['status']); ?>"
     data-deskripsi="<?= htmlspecialchars($l['deskripsi']); ?>"
     data-gambar="<?= !empty($l['gambar']) ? 'uploads/' . $l['gambar'] : 'assets/default.jpg'; ?>"
>

                                <img src="<?= !empty($l['gambar']) ? 'uploads/' . $l['gambar'] : 'assets/default.jpg'; ?>"
     class="lapangan-click"
     style="cursor:pointer"
     alt="<?= htmlspecialchars($l['nama']); ?>">


                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-success"><?= htmlspecialchars($l['nama']); ?></h5>
                                    <p><strong>Lokasi:</strong> <?= htmlspecialchars($l['lokasi']); ?></p>
                                    <p><strong>Harga / Jam:</strong> Rp <?= number_format($l['harga_per_jam'], 0, ",", "."); ?></p>
                                    <p><strong>Status:</strong>
                                        <span
                                            class="badge bg-<?= strtolower($l['status']) == 'tersedia' ? 'success' : 'secondary'; ?>">
                                        <?= ucfirst($l['status']); ?>
                                        </span>
                                    </p>

                                <?php if (strtolower($l['status']) == 'tersedia') { ?>
                                    <?php if ($logged_in) { ?>
                                            <a href="user/booking.php?id=<?= $l['id_lapangan']; ?>"
                                                class="btn btn-success w-100 mt-auto">Booking Sekarang</a>
                                    <?php } else { ?>
                                            <a href="login.php?redirect=index.php" class="btn btn-outline-success w-100 mt-auto">Login untuk
                                                Booking</a>
                                    <?php } ?>
                                <?php } else { ?>
                                        <button class="btn btn-secondary w-100 mt-auto" disabled>Tidak Tersedia</button>
                                <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php
                } // end while
            
            } else {
                ?>
                    <div class="col-12 text-center mt-5">
                        <div class="alert alert-info">Tidak ada lapangan yang ditemukan.</div>
                    </div>
                <?php
            } // end if hasil query
            ?>

        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-success text-white pt-4 pb-3 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h5 class="fw-bold">Bantuan</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Pusat Bantuan</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Kebijakan & Privasi</a></li>
                    </ul>
                </div>
                <div class="col-md-6 mb-3">
                    <h5 class="fw-bold">Sosial Media</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Instagram</a></li>
                        <li><a href="#" class="text-white text-decoration-none">WhatsApp</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Facebook</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-light">
            <p class="text-center mb-0">© <?= date("Y"); ?> Sport Field Rental. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // Realtime notifikasi
        function loadNotif() {
            $.ajax({
                url: 'user/notif_fetch.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    const badge = $('#notifBadge');
                    const list = $('#notifList');
                    if (data.count > 0) {
                        badge.text(data.count).show();
                    } else { badge.hide(); }

                    if (data.list.length > 0) {
                        let html = '';
                        data.list.forEach(n => {
                            html += `<li class="notif-item border-bottom py-1"><a href="user/notifikasi.php" class="text-decoration-none text-dark">${n.judul}</a></li>`;
                        });
                        html += `<li class="text-center mt-1"><a href="user/notifikasi.php">Lihat semua notifikasi</a></li>`;
                        list.html(html);
                    } else {
                        list.html('<li class="text-center">Tidak ada notifikasi</li>');
                    }
                }
            });
        }

        // Load pertama
        loadNotif();
        // Refresh setiap 5 detik
        setInterval(loadNotif, 5000);
    </script>

    <script>
$(document).on('click', '.lapangan-click', function () {
    const card = $(this).closest('.lapangan-card');

    $('#modalNama').text(card.data('nama'));
    $('#modalLokasi').text(card.data('lokasi'));
    $('#modalHarga').text(card.data('harga'));
    $('#modalStatus').text(card.data('status'));
    $('#modalDeskripsi').text(card.data('deskripsi') || 'Tidak ada deskripsi.');
    $('#modalGambar').attr('src', card.data('gambar'));

    const modal = new bootstrap.Modal(document.getElementById('modalLapangan'));
    modal.show();
});
</script>


        <div class="modal fade" id="modalLapangan" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-success" id="modalNama"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <img id="modalGambar" class="img-fluid rounded mb-3">

        <p><strong>📍 Lokasi:</strong> <span id="modalLokasi"></span></p>
        <p><strong>💰 Harga / Jam:</strong> Rp <span id="modalHarga"></span></p>
        <p><strong>⚽ Status:</strong> <span id="modalStatus"></span></p>

        <hr>

        <p class="text-muted" id="modalDeskripsi"></p>
      </div>
    </div>
  </div>
</div>


</body>

</html>