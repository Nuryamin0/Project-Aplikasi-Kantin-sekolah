<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */
$query_menu = "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC";
$daftar_menu = mysqli_query($koneksi, $query_menu);

if (isset($_POST['submit'])) {
    $nama_pembeli = $_POST['nama_pembeli'];
    $id_menu      = $_POST['id_menu'];
    $jumlah       = $_POST['jumlah'];
    $query_harga = "SELECT harga FROM menu WHERE id_menu = '$id_menu'";
    $ambil_harga = mysqli_query($koneksi, $query_harga);
    $data_menu   = mysqli_fetch_assoc($ambil_harga);
    
    $harga    = $data_menu['harga'];
    $subtotal = $harga * $jumlah;
    $kode_transaksi = "TRX-" . date("dmY") . "-" . rand(10, 99);
    $query_transaksi = "INSERT INTO transaksi (kode_transaksi, nama_pembeli, total_bayar) 
                        VALUES ('$kode_transaksi', '$nama_pembeli', '$subtotal')";
    mysqli_query($koneksi, $query_transaksi);

    $id_transaksi_baru = mysqli_insert_id($koneksi);
    $query_detail = "INSERT INTO detail_transaksi (id_transaksi, id_menu, jumlah, subtotal) 
                     VALUES ('$id_transaksi_baru', '$id_menu', '$jumlah', '$subtotal')";
    $simpan = mysqli_query($koneksi, $query_detail);

    if ($simpan) {
        mysqli_query($koneksi, "UPDATE menu SET stok = stok - $jumlah WHERE id_menu = '$id_menu'");
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Gagal memproses transaksi!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Kantin - Tambah Transaksi</title>
</head>
<body>

    <h1>Input Transaksi Baru (Kasir)</h1>
    <div class="navigasi">
        <a href="../index.php">🏠 Dashboard Utama</a>
        <a href="../menu/index.php">🍔 Kelola Menu</a>
        <a href="index.php">💰 Kasir & Riwayat Transaksi</a>
    </div>


    <a href="index.php" class="btn-tambah" style="background-color: #6c757d;"><- Kembali ke Riwayat</a>

    <form action="tambah.php" method="post">
        <table style="max-width: 600px;">
            <thead>
                <tr>
                    <th colspan="2">Form Kasir Kantin Sekolah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width: 30%;"><strong>Nama Pembeli</strong></td>
                    <td>
                        <input type="text" name="nama_pembeli" style="width: 95%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Masukkan nama siswa..." required>
                    </td>
                </tr>
                <tr>
                    <td><strong>Pilih Menu</strong></td>
                    <td>
                        <select name="id_menu" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                            <option value="">-- Pilih Makanan/Minuman --</option>
                            <?php while($menu = mysqli_fetch_assoc($daftar_menu)) { ?>
                                <option value="<?php echo $menu['id_menu']; ?>">
                                    <?php echo $menu['nama_menu']; ?> - (Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><strong>Jumlah Beli</strong></td>
                    <td>
                        <input type="number" name="jumlah" min="1" style="width: 95%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="0" required>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" name="submit" value="Proses Pembayaran" class="btn-tambah" style="margin-bottom: 0; cursor: pointer; border: none; width: 100%;">
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

</body>
</html>
