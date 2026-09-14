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
</head>
<body>

    <h1>Edit Data Menu</h1>
    <?php if (file_exists(__DIR__ . '/../includes/nav_admin.php')) require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="../index.php" class="btn-tambah" style="background-color: #64748b;">← Kembali ke Daftar Menu</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-top: 15px;">
            <span class="alert-icon">⚠️</span>
            <div><?= htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="table-container" style="max-width: 600px; padding: 24px; margin-top: 15px;">
        <form action="edit.php?id_menu=<?= $id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama_menu">Nama Menu / Produk</label>
                <input type="text" id="nama_menu" name="nama_menu" value="<?= htmlspecialchars($lama['nama_menu'] ?? $lama['nama_produk'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Makanan" <?= ($lama['kategori'] == 'Makanan') ? 'selected' : ''; ?>>Makanan</option>
                    <option value="Minuman" <?= ($lama['kategori'] == 'Minuman') ? 'selected' : ''; ?>>Minuman</option>
                    <option value="Cemilan" <?= ($lama['kategori'] == 'Cemilan') ? 'selected' : ''; ?>>Cemilan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="harga">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" min="0" value="<?= htmlspecialchars($lama['harga']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>

            <div class="form-group">
                <label for="stok">Stok</label>
                <input type="number" id="stok" name="stok" min="0" value="<?= htmlspecialchars($lama['stok']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
            </div>

            <div class="form-group">
                <label for="foto">Foto Menu</label>
                <?php if (!empty($lama['foto'])): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="uploads/<?= htmlspecialchars($lama['foto']); ?>" alt="Foto Sekarang" class="img-thumb" style="width: 60px; height: 60px;">
                        <small style="color: #64748b; display: block;">Foto saat ini: <?= htmlspecialchars($lama['foto']); ?></small>
                    </div>
                <?php endif; ?>
                <input type="file" id="foto" name="foto" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <small class="form-hint">Kosongkan jika tidak ingin mengganti foto saat ini.</small>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" name="submit" class="btn-primary" style="margin-top: 0; flex: 1;">Update Data Menu</button>
                <a href="../index.php" class="btn-action" style="background-color: #e2e8f0; color: #334155; padding: 11px 16px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">Batal</a>
            </div>
        </form>
    </div>

</body>
</html>
