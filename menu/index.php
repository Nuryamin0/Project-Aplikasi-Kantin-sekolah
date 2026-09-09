<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$query = "SELECT * FROM menu ORDER BY id_menu DESC";
$hasil = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Daftar Menu</title>
</head>
<body>

    <h1>Manajemen Menu Kantin</h1>
    <div class="navigasi">
        <a href="../index.php">🏠 Dashboard Utama</a>
        <a href="index.php" style="color: #333; cursor: default;">🍔 Kelola Menu (Aktif)</a>
        <a href="../transaksi/index.php">💰 Kasir & Riwayat Transaksi</a>
    </div>

    <a href="tambah.php" class="btn-tambah">[+] Tambah Menu Baru</a>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Foto</th>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($data = mysqli_fetch_assoc($hasil)) { 
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td>
                    <?php if (!empty($data['foto'])): ?>
                        <img src="../assets/uploads/<?php echo $data['foto']; ?>" alt="Foto Menu">
                    <?php else: ?>
                        <span style="color: #999; font-style: italic;">Tidak ada foto</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $data['nama_menu']; ?></td>
                <td><?php echo $data['kategori']; ?></td>
                <td>Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?></td>
                <td><?php echo $data['stok']; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $data['id_menu']; ?>" style="color: #ffc107; margin-right: 10px;">Edit</a>
                    <a href="hapus.php?id=<?php echo $data['id_menu']; ?>" style="color: #dc3545;" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?')">Hapus</a>
                </td>
            </tr>
            <?php } ?>
            
            <?php if (mysqli_num_rows($hasil) == 0): ?>
            <tr>
                <td colspan="7" style="text-align: center; font-style: italic;">Belum ada data menu di kantin.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
