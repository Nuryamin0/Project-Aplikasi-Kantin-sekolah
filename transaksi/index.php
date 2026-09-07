<?php
require_once __DIR__ . "/../config/koneksi.php";

/** @var mysqli $koneksi */

$query = "SELECT * FROM transaksi ORDER BY tanggal_transaksi DESC";
$hasil = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kantin Sekolah - Riwayat Transaksi</title>
</head>
<body>
    <h2>Riwayat Transaksi Kantin</h2>
    <a href="tambah.php">[+] Input Transaksi Baru (Kasir)</a> | 
    <a href="../menu/index.php">Lihat Daftar Menu</a>
    <br><br>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>No</th>
            <th>Kode Transaksi</th>
            <th>Nama Pembeli</th>
            <th>Tanggal</th>
            <th>Total Bayar</th>
            <th>Aksi</th>
        </tr>
        <?php 
        $no = 1;
        while($data = mysqli_fetch_assoc($hasil)) { 
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><strong><?php echo $data['kode_transaksi']; ?></strong></td>
            <td><?php echo $data['nama_pembeli']; ?></td>
            <td><?php echo $data['tanggal_transaksi']; ?></td>
            <td>Rp <?php echo number_format($data['total_bayar']); ?></td>
            <td>
                <a href="detail.php?id=<?php echo $data['id_transaksi']; ?>">Lihat Detail Item</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
