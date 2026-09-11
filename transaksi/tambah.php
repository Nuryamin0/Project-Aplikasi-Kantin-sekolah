<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$query_menu = "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC";
$daftar_menu = mysqli_query($koneksi, $query_menu);

$error = '';

if (isset($_POST['submit'])) {
    $nama_pembeli = trim($_POST['nama_pembeli'] ?? '');
    $id_menu      = (int) ($_POST['id_menu'] ?? 0);
    $jumlah       = (int) ($_POST['jumlah'] ?? 0);

    if (empty($nama_pembeli) || $id_menu <= 0 || $jumlah <= 0) {
        $error = "Semua kolom wajib diisi dengan benar!";
    } else {
        $stmt_menu = mysqli_prepare($koneksi, "SELECT harga, stok FROM menu WHERE id_menu = ?");
        mysqli_stmt_bind_param($stmt_menu, "i", $id_menu);
        mysqli_stmt_execute($stmt_menu);
        $data_menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_menu));
        mysqli_stmt_close($stmt_menu);

        if (!$data_menu) {
            $error = "Menu yang dipilih tidak valid!";
        } elseif ($jumlah > $data_menu['stok']) {
            $error = "Jumlah pembelian melebihi stok yang tersedia (Sisa stok: " . $data_menu['stok'] . ")!";
        } else {
            $harga    = $data_menu['harga'];
            $subtotal = $harga * $jumlah;
            $kode_transaksi = "TRX-" . date("dmY") . "-" . rand(10, 99);

            $stmt_tx = mysqli_prepare($koneksi, "INSERT INTO transaksi (kode_transaksi, nama_pembeli, total_bayar) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_tx, "ssi", $kode_transaksi, $nama_pembeli, $subtotal);
            mysqli_stmt_execute($stmt_tx);
            $id_transaksi_baru = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_tx);

            $stmt_dt = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_menu, jumlah, subtotal) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_dt, "iiii", $id_transaksi_baru, $id_menu, $jumlah, $subtotal);
            $simpan = mysqli_stmt_execute($stmt_dt);
            mysqli_stmt_close($stmt_dt);

            if ($simpan) {
                $stmt_upd = mysqli_prepare($koneksi, "UPDATE menu SET stok = stok - ? WHERE id_menu = ?");
                mysqli_stmt_bind_param($stmt_upd, "ii", $jumlah, $id_menu);
                mysqli_stmt_execute($stmt_upd);
                mysqli_stmt_close($stmt_upd);

                header("Location: index.php");
                exit();
            } else {
                $error = "Gagal memproses transaksi: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Kantin - Tambah Transaksi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Input Transaksi Baru (Kasir)</h1>
    <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

    <a href="index.php" class="btn-tambah" style="background-color: #64748b;">← Kembali ke Riwayat</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-top: 15px;">
            <span class="alert-icon">⚠️</span>
            <div><?= htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="table-container" style="max-width: 600px; padding: 24px; margin-top: 15px;">
        <form action="tambah.php" method="POST">
            <div class="form-group">
                <label for="nama_pembeli">Nama Pembeli / Siswa</label>
                <input type="text" id="nama_pembeli" name="nama_pembeli" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Masukkan nama pembeli..." required>
            </div>

            <div class="form-group">
                <label for="id_menu">Pilih Menu</label>
                <select id="id_menu" name="id_menu" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                    <option value="">-- Pilih Makanan / Minuman --</option>
                    <?php while ($menu = mysqli_fetch_assoc($daftar_menu)): ?>
                        <option value="<?= $menu['id_menu']; ?>">
                            <?= htmlspecialchars($menu['nama_menu']); ?> (Stok: <?= $menu['stok']; ?>) - Rp <?= number_format($menu['harga'], 0, ',', '.'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah Beli</label>
                <input type="number" id="jumlah" name="jumlah" min="1" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="1" required>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="submit" class="btn-primary btn-success-action" style="margin-top: 0;">Proses Transaksi Kasir</button>
            </div>
        </form>
    </div>

</body>
</html>
