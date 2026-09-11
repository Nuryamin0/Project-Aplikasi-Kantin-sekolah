<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$error = '';

if (isset($_POST['submit'])) {
    $nama_menu = trim($_POST['nama_menu'] ?? $_POST['nama_produk'] ?? '');
    $kategori  = $_POST['kategori'] ?? '';
    $harga     = (int) ($_POST['harga'] ?? 0);
    $stok      = (int) ($_POST['stok'] ?? 0);

    // Proses upload foto jika ada
    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($_FILES['foto']['name']));
        $target_dir = __DIR__ . '/uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . $foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO menu (nama_menu, kategori, harga, stok, foto) VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "ssiis", $nama_menu, $kategori, $harga, $stok, $foto);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Data gagal ditambahkan: " . mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Menu</title>
</head>
<body>

    <h2>Tambah Menu Kantin</h2>

    <p><a href="../index.php">← Kembali</a></p>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="tambah.php" method="POST" enctype="multipart/form-data">
        <p>
            <label for="nama_menu">Nama Menu:</label><br>
            <input type="text" id="nama_menu" name="nama_menu" required>
        </p>

        <p>
            <label for="kategori">Kategori:</label><br>
            <select id="kategori" name="kategori" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Makanan">Makanan</option>
                <option value="Minuman">Minuman</option>
                <option value="Cemilan">Cemilan</option>
            </select>
        </p>

        <p>
            <label for="harga">Harga (Rp):</label><br>
            <input type="number" id="harga" name="harga" min="0" required>
        </p>

        <p>
            <label for="stok">Stok Awal:</label><br>
            <input type="number" id="stok" name="stok" min="0" required>
        </p>

        <p>
            <label for="foto">Foto Menu:</label><br>
            <input type="file" id="foto" name="foto" accept="image/*">
        </p>

        <p>
            <button type="submit" name="submit">Simpan</button>
        </p>
    </form>

</body>
</html>
