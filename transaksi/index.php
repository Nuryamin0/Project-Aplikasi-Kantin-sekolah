<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

// Auto-migration jika kolom catatan belum ada
$cek_catatan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_transaksi LIKE 'catatan'");
$has_catatan = ($cek_catatan && mysqli_num_rows($cek_catatan) > 0);
if (!$has_catatan) {
    @mysqli_query($koneksi, "ALTER TABLE detail_transaksi ADD COLUMN catatan TEXT NULL AFTER subtotal");
    $cek_catatan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_transaksi LIKE 'catatan'");
    $has_catatan = ($cek_catatan && mysqli_num_rows($cek_catatan) > 0);
}

// Auto-migration jika kolom status_konfirmasi belum ada
$cek_status_tx = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'status_konfirmasi'");
if ($cek_status_tx && mysqli_num_rows($cek_status_tx) === 0) {
    @mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN status_konfirmasi ENUM('menunggu','dikonfirmasi') NOT NULL DEFAULT 'menunggu' AFTER total_bayar");
}

// Proses konfirmasi pesanan oleh penjual/admin
if (isset($_GET['konfirmasi'])) {
    $id_konfirmasi = (int) $_GET['konfirmasi'];
    $stmt_konf = mysqli_prepare($koneksi, "UPDATE transaksi SET status_konfirmasi = 'dikonfirmasi' WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmt_konf, "i", $id_konfirmasi);
    mysqli_stmt_execute($stmt_konf);
    mysqli_stmt_close($stmt_konf);
    header("Location: index.php");
    exit();
}

$catatan_expr = $has_catatan
    ? "GROUP_CONCAT(CASE WHEN dt.catatan IS NULL OR dt.catatan = '' THEN NULL ELSE CONCAT(m.nama_menu, ': ', dt.catatan) END SEPARATOR ', ') AS catatan_pesanan"
    : "NULL AS catatan_pesanan";

$query = "
    SELECT t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar, t.status_konfirmasi,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' (x', dt.jumlah, ')') SEPARATOR ', ') AS item_dibeli,
           $catatan_expr
    FROM transaksi t
    LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN menu m ON dt.id_menu = m.id_menu
    GROUP BY t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar, t.status_konfirmasi
    ORDER BY t.tanggal_transaksi DESC
";
$hasil = mysqli_query($koneksi, $query);
if (!$hasil) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Riwayat Transaksi</title>

    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="transaksi-page">

<div class="sub-tagline">Fresh • Sehat • Enak • Bersahabat</div>

<div class="container">
    <div class="card">
        
        <!-- Header -->
        <div class="card-header">
            <div>
                <h2>KANTIN SEHAT</h2>
                <p>Riwayat dan pemantauan transaksi kasir</p>
            </div>
            <div class="btn-group">
                <a href="../index.php" class="btn btn-light">Kembali</a>
            </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">No</th>
                            <th style="width: 12%;">Kode</th>
                            <th style="width: 15%;">Nama Pembeli</th>
                            <th style="width: 13%;">Tanggal</th>
                            <th style="width: 11%;">Total</th>
                            <th style="width: 17%;">Catatan</th>
                            <th style="width: 13%; text-align: center;">Status</th>
                            <th style="width: 15%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($hasil) > 0) {
                            while($data = mysqli_fetch_assoc($hasil)) { 
                                $id_transaksi = $data['id_transaksi'];
                        ?>
                        <tr>
                            <td style="text-align: center; color: #a0aec0;"><?php echo $no++; ?></td>
                            <td><span class="badge-code"><?php echo htmlspecialchars($data['kode_transaksi']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($data['nama_pembeli']); ?></strong></td>
                            <td style="color: #718096;"><?php echo htmlspecialchars($data['tanggal_transaksi']); ?></td>
                            <td class="total-price">Rp <?php echo number_format($data['total_bayar'], 0, ',', '.'); ?></td>
                            <td><?php echo !empty($data['catatan_pesanan']) ? htmlspecialchars($data['catatan_pesanan']) : '-'; ?></td>
                            <td style="text-align: center;">
                                <?php if ($data['status_konfirmasi'] === 'dikonfirmasi'): ?>
                                    <span class="badge-status badge-status-confirmed">✅ Dikonfirmasi</span>
                                <?php else: ?>
                                    <span class="badge-status badge-status-pending">⏳ Menunggu</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="detail.php?id=<?php echo $id_transaksi; ?>" class="btn btn-sm btn-info" title="Detail">Detail</a>
                                    <?php if ($data['status_konfirmasi'] !== 'dikonfirmasi'): ?>
                                        <a href="index.php?konfirmasi=<?php echo $id_transaksi; ?>" class="btn btn-sm btn-confirm" title="Konfirmasi Pesanan" onclick="return confirm('Konfirmasi pesanan ini?');">Konfirmasi</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else { 
                        ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                Belum ada data transaksi.
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