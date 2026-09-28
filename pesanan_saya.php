<?php
session_start();
include __DIR__ . '/config/koneksi.php';

/** @var mysqli $koneksi */

// Hanya bisa diakses oleh user yang sudah login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$id_user = (int) $_SESSION['id_user'];
$nama_saya = $_SESSION['nama'] ?? '';

// Auto-migration jaga-jaga jika kolom belum tersedia (mis. halaman dibuka sebelum index.php)
$cek_kolom_idu = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'id_user'");
if ($cek_kolom_idu && mysqli_num_rows($cek_kolom_idu) === 0) {
    @mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN id_user INT NULL AFTER nama_pembeli");
}
$cek_kolom_status_tx = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'status_konfirmasi'");
if ($cek_kolom_status_tx && mysqli_num_rows($cek_kolom_status_tx) === 0) {
    @mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN status_konfirmasi ENUM('menunggu','dikonfirmasi') NOT NULL DEFAULT 'menunggu' AFTER total_bayar");
}

$cek_catatan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_transaksi LIKE 'catatan'");
$has_catatan = ($cek_catatan && mysqli_num_rows($cek_catatan) > 0);
$catatan_expr = $has_catatan
    ? "GROUP_CONCAT(CASE WHEN dt.catatan IS NULL OR dt.catatan = '' THEN NULL ELSE CONCAT(m.nama_menu, ': ', dt.catatan) END SEPARATOR ', ') AS catatan_pesanan"
    : "NULL AS catatan_pesanan";

// Ambil pesanan milik user ini. Menyertakan juga pesanan lama (sebelum kolom id_user
// ditambahkan) dengan mencocokkan nama pembeli, supaya riwayat lama tetap muncul.
$stmt = mysqli_prepare($koneksi, "
    SELECT t.id_transaksi, t.kode_transaksi, t.tanggal_transaksi, t.total_bayar, t.status_konfirmasi,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' (x', dt.jumlah, ')') SEPARATOR ', ') AS item_dibeli,
           $catatan_expr
    FROM transaksi t
    LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN menu m ON dt.id_menu = m.id_menu
    WHERE t.id_user = ? OR (t.id_user IS NULL AND t.nama_pembeli = ?)
    GROUP BY t.id_transaksi, t.kode_transaksi, t.tanggal_transaksi, t.total_bayar, t.status_konfirmasi
    ORDER BY t.tanggal_transaksi DESC
");
mysqli_stmt_bind_param($stmt, "is", $id_user, $nama_saya);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Pesanan Saya</title>

    <link rel="stylesheet" href="assets/style.css">
    <style>
    /* Rapikan header Pesanan Saya */
    .transaksi-page .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }
    /* Teks atas */
    .transaksi-page .sub-tagline {
        text-align: center;
        margin: 20px 0 10px;
        color: #4a8f5a;
        font-size: 14px;
        font-weight: 600;
    }

    /* Tombol kembali */
    .transaksi-page .btn-light {
        display: inline-block;
        padding: 9px 15px;
        background: white;
        color: #39834d;
        border-radius: 7px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #6bd582da;
    }

    .transaksi-page .btn-light:hover {
        background: #d5fddf;
    }

    /* Kalau layar kecil */
    @media (max-width: 600px) {
        .transaksi-page .card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .transaksi-page .btn-group {
            width: 100%;
        }

        .transaksi-page .btn-light {
            text-align: center;
        }
    }
</style>
</head>
<body class="transaksi-page">

<div class="sub-tagline">Fresh • Sehat • Enak • Bersahabat</div>

<div class="container">
    <div class="card">

        <!-- Header -->
        <div class="card-header">
            <div>
                <h2>PESANAN SAYA</h2>
                <p>Pantau status konfirmasi pesananmu di sini</p>
            </div>
            <div class="btn-group">
                <a href="index.php" class="btn btn-light">Kembali ke Menu</a>
            </div>
        </div>

        <!-- Tabel Pesanan -->
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">No</th>
                            <th style="width: 13%;">Kode</th>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 24%;">Pesanan</th>
                            <th style="width: 16%;">Catatan</th>
                            <th style="width: 12%;">Total</th>
                            <th style="width: 15%; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($hasil && mysqli_num_rows($hasil) > 0) {
                            while ($data = mysqli_fetch_assoc($hasil)) {
                                $sudah_konfirmasi = ($data['status_konfirmasi'] === 'dikonfirmasi');
                        ?>
                        <tr>
                            <td style="text-align: center; color: #a0aec0;"><?php echo $no++; ?></td>
                            <td><span class="badge-code"><?php echo htmlspecialchars($data['kode_transaksi']); ?></span></td>
                            <td style="color: #718096;"><?php echo htmlspecialchars($data['tanggal_transaksi']); ?></td>
                            <td><?php echo !empty($data['item_dibeli']) ? htmlspecialchars($data['item_dibeli']) : '-'; ?></td>
                            <td><?php echo !empty($data['catatan_pesanan']) ? htmlspecialchars($data['catatan_pesanan']) : '-'; ?></td>
                            <td class="total-price">Rp <?php echo number_format($data['total_bayar'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php if ($sudah_konfirmasi): ?>
                                    <span class="badge-status badge-status-confirmed">✅ Dikonfirmasi</span>
                                <?php else: ?>
                                    <span class="badge-status badge-status-pending">⏳ Menunggu Konfirmasi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                Kamu belum pernah memesan. Yuk jajan dulu! 🍽️
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>
