<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id_transaksi = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT d.*, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar, m.nama_menu, m.harga
     FROM detail_transaksi d
     INNER JOIN transaksi t ON d.id_transaksi = t.id_transaksi
     INNER JOIN menu m ON d.id_menu = m.id_menu
     WHERE d.id_transaksi = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$items = [];
$info_transaksi = null;
while ($row = mysqli_fetch_assoc($hasil)) {
    if (!$info_transaksi) {
        $info_transaksi = $row;
    }
    $items[] = $row;
}
mysqli_stmt_close($stmt);

// Jika detail_transaksi kosong tetapi transaksi ada
if (!$info_transaksi && $id_transaksi > 0) {
    $stmt_fallback = mysqli_prepare($koneksi, "SELECT * FROM transaksi WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmt_fallback, "i", $id_transaksi);
    mysqli_stmt_execute($stmt_fallback);
    $info_transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_fallback));
    mysqli_stmt_close($stmt_fallback);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Nota - <?= htmlspecialchars($info_transaksi['kode_transaksi'] ?? 'Tidak Ditemukan'); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Rincian Nota Transaksi</h1>
    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="index.php" class="btn-tambah" style="background-color: #64748b;">← Kembali ke Riwayat Transaksi</a>

    <?php if ($info_transaksi): ?>
    <div class="table-container" style="max-width: 650px; padding: 20px; margin-top: 15px; margin-bottom: 20px;">
        <h2 style="font-size: 1.1rem; margin-bottom: 12px; color: #1e293b;">Informasi Pembeli & Nota</h2>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 35%; border: none; padding: 6px 0;"><strong>Kode Transaksi</strong></td>
                <td style="border: none; padding: 6px 0;">: <span class="badge badge-kat"><?= htmlspecialchars($info_transaksi['kode_transaksi']); ?></span></td>
            </tr>
            <tr>
                <td style="border: none; padding: 6px 0;"><strong>Nama Pembeli</strong></td>
                <td style="border: none; padding: 6px 0;">: <?= htmlspecialchars($info_transaksi['nama_pembeli']); ?></td>
            </tr>
            <tr>
                <td style="border: none; padding: 6px 0;"><strong>Waktu Transaksi</strong></td>
                <td style="border: none; padding: 6px 0;">: <?= date('d/m/Y H:i:s', strtotime($info_transaksi['tanggal_transaksi'])); ?></td>
            </tr>
        </table>
    </div>

    <div class="table-container" style="max-width: 650px;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Menu</th>
                    <th>Harga Satuan</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $grand_total = 0;
                foreach ($items as $item): 
                    $grand_total += $item['subtotal'];
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><strong><?= htmlspecialchars($item['nama_menu']); ?></strong></td>
                    <td>Rp <?= number_format($item['harga'], 0, ',', '.'); ?></td>
                    <td><?= $item['jumlah']; ?>x</td>
                    <td>Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; font-style: italic;">Tidak ada rincian item.</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc;">
                    <td colspan="4" style="text-align: right; font-weight: bold; padding: 14px 16px;">Total Pembayaran:</td>
                    <td style="font-weight: bold; color: #16a34a; font-size: 1.05rem; padding: 14px 16px;">
                        Rp <?= number_format($info_transaksi['total_bayar'] ?? $grand_total, 0, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
        <div class="alert alert-danger" style="margin-top: 15px;">
            <span class="alert-icon">⚠️</span>
            <div>Data transaksi tidak ditemukan atau sudah dihapus.</div>
        </div>
    <?php endif; ?>

</body>
</html>
