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
<<<<<<< HEAD
        header("Location: index.php");
=======
        header("Location: ../index.php");
>>>>>>> main
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
<<<<<<< HEAD
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu Kantin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Tambah Menu Kantin</h1>
    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="index.php" class="btn-tambah" style="background-color: #64748b;">← Kembali ke Daftar Menu</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-top: 15px;">
            <span class="alert-icon">⚠️</span>
            <div><?= htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="table-container" style="max-width: 600px; padding: 24px; margin-top: 15px;">
        <form action="tambah.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama_menu">Nama Menu / Produk</label>
                <input type="text" id="nama_menu" name="nama_menu" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Contoh: Nasi Goreng Spesial" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Cemilan">Cemilan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="harga">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Contoh: 10000" required>
            </div>

            <div class="form-group">
                <label for="stok">Stok Awal</label>
                <input type="number" id="stok" name="stok" min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Contoh: 20" required>
            </div>

            <div class="form-group">
                <label for="foto">Foto Menu</label>
                <input type="file" id="foto" name="foto" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <small class="form-hint">Format foto: JPG, PNG, atau GIF (Opsional).</small>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="submit" class="btn-primary" style="margin-top: 0;">Simpan Menu</button>
            </div>
        </form>
    </div>
=======
    <title>Tambah Menu</title>
</head>
<body>

    <h2>Tambah Menu Kantin</h2>

    

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
            <p><a href="../index.php">← Kembali</a></p>
        </p>
    </form>
>>>>>>> main

</body>
</html>
