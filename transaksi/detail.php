<?php
require_once __DIR__ . "/../config/koneksi.php";

/** @var mysqli $koneksi */

$id_transaksi = $_GET['id'];

// Query JOIN menggabungkan detail, transaksi induk, dan nama menu asli
$query = "SELECT d.*, t.kode_transaksi, t.nama_pembeli, m.nama_menu, m.harga 
          FROM detail_transaksi d
          INNER JOIN transaksi t ON d.id_transaksi = t.id_transaksi
          INNER JOIN menu m ON d.id_menu = m.id_menu
          WHERE d.id_transaksi = '$id_transaksi'";

$hasil = mysqli_query($koneksi, $query);
$data  = mysqli_fetch_assoc($hasil);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail Nota - <?php echo $data['kode_transaksi'] ?? 'Tidak Ditemukan'; ?></title>
</head>
<body>
    <h2>Rincian Item Pembelian</h2>
    <a href="index.php"><- Kembali ke Riwayat</a>
    <br><br>

    <?php if ($data) { ?>
    <table cellpadding="4">
        <tr>
            <td><strong>Kode Nota</strong></td>
            <td>: <?php echo $data['kode_transaksi']; ?></td>
        </tr>
        <tr>
            <td><strong>Nama Pelanggan</strong></td>
            <td>: <?php echo $data['nama_pembeli']; ?></td>
        </tr>
    </table>
    <br>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Nama Menu</th>
            <th>Harga Satuan</th>
            <th>Jumlah Beli</th>
            <th>Subtotal</th>
        </tr>
        <tr>
            <td><?php echo $data['nama_menu']; ?></td>
            <td>Rp <?php echo number_format($data['harga']); ?></td>
            <td><?php echo $data['jumlah']; ?>x</td>
            <td><strong>Rp <?php echo number_format($data['subtotal']); ?></strong></td>
        </tr>
    </table>
    <?php } else { echo "<p>Detail transaksi kosong atau data tidak ditemukan.</p>"; } ?>
</body>
</html>
