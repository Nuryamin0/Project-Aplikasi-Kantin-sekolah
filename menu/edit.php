<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id = (int) ($_GET['id_menu'] ?? 0);
$error = '';

if ($id <= 0) {
    header("Location: ../index.php");
    exit();
}

// Ambil data menu saat ini
$stmt_ambil = mysqli_prepare($koneksi, "SELECT * FROM menu WHERE id_menu = ?");
mysqli_stmt_bind_param($stmt_ambil, "i", $id);
mysqli_stmt_execute($stmt_ambil);
$lama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ambil));
mysqli_stmt_close($stmt_ambil);

if (!$lama) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $nama_menu = trim($_POST['nama_menu'] ?? $_POST['nama_produk'] ?? '');
    $kategori  = $_POST['kategori'] ?? '';
    $harga     = (int) ($_POST['harga'] ?? 0);
    $stok      = (int) ($_POST['stok'] ?? 0);

    // Ambil foto lama
    $foto = $lama['foto'] ?? '';

    // Ganti foto jika ada file baru yang diunggah
    if (!empty($_FILES['foto']['name'])) {
        $foto_baru = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($_FILES['foto']['name']));
        $target_dir = __DIR__ . '/uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . $foto_baru;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto = $foto_baru;
        }
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, stok = ?, foto = ? WHERE id_menu = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssiisi", $nama_menu, $kategori, $harga, $stok, $foto, $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Gagal mengupdate data: " . mysqli_error($koneksi);
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Menu - Kantin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
            padding: 20px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>

    <div style="width: 100%; max-width: 600px;">

        <!-- 1. JUDUL DI ATAS TANPA TOMBOL SAMPING -->
        <h1 style="margin: 0 0 15px 0; font-size: 1.75rem; color: #1e293b;">Edit Data Menu</h1>

        <!-- 2. NAVIGASI IKON RUMAH (BERFUNGSI SEBAGAI TOMBOL KEMBALI) -->
        <?php if (file_exists(__DIR__ . '/../includes/nav_admin.php')): ?>
            <div style="margin-bottom: 15px;">
                <a href="../index.php" style="text-decoration: none; color: inherit; display: block;">
                    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>
                </a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 15px;">
                <a href="../index.php" class="table-container" style="display: block; padding: 12px 20px; background: white; border-radius: 8px; text-decoration: none; color: #065f46; box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);">
                    🏠 Dashboard & Data Menu
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 15px;">
                <span class="alert-icon">⚠️</span>
                <div><?= htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <!-- 3. KOTAK FORM UTAMA -->
        <div class="table-container" style="width: 100%; padding: 24px; box-sizing: border-box; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
            <form action="edit.php?id_menu=<?= $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="nama_menu" style="display: block; margin-bottom: 5px; font-weight: 500;">Nama Menu / Produk</label>
                    <input type="text" id="nama_menu" name="nama_menu" value="<?= htmlspecialchars($lama['nama_menu'] ?? $lama['nama_produk'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="kategori" style="display: block; margin-bottom: 5px; font-weight: 500;">Kategori</label>
                    <select id="kategori" name="kategori" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Makanan" <?= ($lama['kategori'] == 'Makanan') ? 'selected' : ''; ?>>Makanan</option>
                        <option value="Minuman" <?= ($lama['kategori'] == 'Minuman') ? 'selected' : ''; ?>>Minuman</option>
                        <option value="Cemilan" <?= ($lama['kategori'] == 'Cemilan') ? 'selected' : ''; ?>>Cemilan</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="harga" style="display: block; margin-bottom: 5px; font-weight: 500;">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0" value="<?= htmlspecialchars($lama['harga']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="stok" style="display: block; margin-bottom: 5px; font-weight: 500;">Stok</label>
                    <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($lama['stok']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="foto" style="display: block; margin-bottom: 5px; font-weight: 500;">Foto Menu</label>
                    <?php if (!empty($lama['foto'])): ?>
                        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                            <img src="uploads/<?= htmlspecialchars($lama['foto']); ?>" alt="Foto Sekarang" class="img-thumb" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <small style="color: #64748b;">Foto saat ini: <?= htmlspecialchars($lama['foto']); ?></small>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="foto" name="foto" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    <small class="form-hint" style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 4px;">Kosongkan jika tidak ingin mengganti foto saat ini.</small>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="submit" class="btn-primary" style="flex: 1; padding: 10px; border: none; border-radius: 6px; cursor: pointer;">Update Data Menu</button>
                    <a href="../index.php" class="btn-action" style="background-color: #e2e8f0; color: #334155; padding: 10px 16px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>