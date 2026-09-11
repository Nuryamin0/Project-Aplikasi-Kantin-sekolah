<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$query = "SELECT * FROM menu ORDER BY id_menu DESC";
$hasil = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Daftar Menu</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Manajemen Menu Kantin</h1>
    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="tambah.php" class="btn-tambah">+ Tambah Menu Baru</a>

    <div class="table-container">
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
                    <td><?= $no++; ?></td>
                    <td>
                        <?php if (!empty($data['foto'])): ?>
                            <img src="uploads/<?= htmlspecialchars($data['foto']); ?>" alt="Foto Menu" class="img-thumb" onerror="this.src='https://via.placeholder.com/45'">
                        <?php else: ?>
                            <span style="color: #999; font-size: 0.8rem; font-style: italic;">Tidak ada foto</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($data['nama_menu'] ?? $data['nama_produk'] ?? ''); ?></strong></td>
                    <td><span class="badge badge-kat"><?= htmlspecialchars($data['kategori']); ?></span></td>
                    <td>Rp <?= number_format($data['harga'], 0, ',', '.'); ?></td>
                    <td>
                        <?= $data['stok']; ?>
                        <?php if ($data['stok'] < 5): ?>
                            <span class="badge badge-stok">Sedikit</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit.php?id_menu=<?= $data['id_menu']; ?>" class="btn-action btn-edit">Edit</a>
                        <a href="hapus.php?id_menu=<?= $data['id_menu']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?')">Hapus</a>
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
    </div>

</body>
</html>
