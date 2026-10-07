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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu Kantin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f8fafc;
            padding: 20px 20px;
            box-sizing: border-box;
        }

        /* Mengatur jarak antar elemen di dalam wrapper utama menjadi lebih rapat (gap: 12px) */
        .main-wrapper {
            width: 100%;
            max-width: 650px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
    </style>
</head>

<body>

    <div class="main-wrapper">
        <!-- 1. JUDUL HALAMAN -->
        <h1 style="margin: 0; font-size: 1.75rem; color: #1e293b;">Tambah Menu Kantin</h1>

        <!-- 2. TOMBOL DASHBOARD DALAM KOTAK PUTIH -->
        <?php if (file_exists(__DIR__ . '/../includes/nav_admin.php')): ?>
            <a href="../index.php" style="text-decoration: none; color: inherit; display: block;">
                <?php require __DIR__ . '/../includes/nav_admin.php'; ?>
            </a>
        <?php else: ?>
            <a href="../index.php" class="table-container" style="display: block; padding: 12px 20px; background: white; border-radius: 8px; text-decoration: none; color: #065f46; box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);">
                🏠 Dashboard & Data Menu
            </a>
        <?php endif; ?>

        <!-- 3. KARTU FORM UTAMA -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 0;">
                <span class="alert-icon">⚠️</span>
                <div><?= htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <div class="table-container" style="width: 100%; padding: 24px; box-sizing: border-box; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin-top: 0;">
            <form action="tambah.php" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="nama_menu" style="display: block; margin-bottom: 5px; font-weight: 500;">Nama Menu / Produk</label>
                    <input type="text" id="nama_menu" name="nama_menu" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="Contoh: Nasi Goreng Spesial" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="kategori" style="display: block; margin-bottom: 5px; font-weight: 500;">Kategori</label>
                    <select id="kategori" name="kategori" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Makanan">Makanan</option>
                        <option value="Minuman">Minuman</option>
                        <option value="Cemilan">Cemilan</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="harga" style="display: block; margin-bottom: 5px; font-weight: 500;">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="Contoh: 10000" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="stok" style="display: block; margin-bottom: 5px; font-weight: 500;">Stok</label>
                    <input type="number" id="stok" name="stok" min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="Contoh: 20" required>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="foto" style="display: block; margin-bottom: 5px; font-weight: 500;">Foto Menu</label>
                    <input type="file" id="foto" name="foto" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    <small class="form-hint" style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 4px;">Format foto: JPG, PNG, atau GIF (Opsional).</small>
                </div>

                <!-- TOMBOL AKSI SEJAJAR (SIMPAN & BATAL) -->
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" name="submit" class="btn-primary" style="flex: 1; padding: 10px; border: none; border-radius: 6px; cursor: pointer; text-align: center;">Simpan Menu</button>
                    <a href="../index.php" style="padding: 10px 20px; background-color: #e2e8f0; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 500; text-align: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>