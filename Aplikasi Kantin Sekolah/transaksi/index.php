<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$query = "
    SELECT t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' (x', dt.jumlah, ')') SEPARATOR ', ') AS item_dibeli
    FROM transaksi t
    LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN menu m ON dt.id_menu = m.id_menu
    GROUP BY t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar
    ORDER BY t.tanggal_transaksi DESC
";
$hasil = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Riwayat Transaksi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Riwayat Transaksi Kantin</h1>
    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="tambah.php" class="btn-tambah" style="background-color: #16a34a;">+ Input Transaksi Baru (Kasir)</a>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode TX</th>
                    <th>Nama Pembeli</th>
                    <th>Detail Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($data = mysqli_fetch_assoc($hasil)) { 
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><strong><?= htmlspecialchars($data['kode_transaksi']); ?></strong></td>
                    <td><?= htmlspecialchars($data['nama_pembeli']); ?></td>
                    <td><?= htmlspecialchars($data['item_dibeli'] ?? 'Tidak ada item'); ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($data['tanggal_transaksi'])); ?></td>
                    <td><strong>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></strong></td>
                    <td>
                        <a href="detail.php?id=<?= $data['id_transaksi']; ?>" class="btn-action btn-edit" style="background-color: #2563eb;">Lihat Detail</a>
                    </td>
                </tr>
                <?php } ?>

                <?php if (mysqli_num_rows($hasil) == 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; font-style: italic;">Belum ada riwayat transaksi.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
