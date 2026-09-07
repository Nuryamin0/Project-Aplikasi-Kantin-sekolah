<?php
require_once __DIR__ . "/../config/koneksi.php";

/** @var mysqli $koneksi */


$query_menu = "SELECT * FROM menu WHERE stok > 0";
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
    <title>Kasir Kantin - Tambah Transaksi</title>
</head>
<body>
    <h2>Form Kasir Kantin Sekolah</h2>
    <a href="index.php"><- Kembali ke Riwayat</a>
    <br><br>

    <form action="tambah.php" method="post">
        <table cellpadding="5">
            <tr>
                <td>Nama Pembeli</td>
                <td><input type="text" name="nama_pembeli" required></td>
            </tr>
            <tr>
               <td>pilih menu</td>
                <td><input type="text" name="nama_produk" required></td>

                        <?php while($menu = mysqli_fetch_assoc($daftar_menu)) { ?>
                            <option value="<?php echo $menu['id_menu']; ?>">
                                <?php echo $menu['nama_menu']; ?> - (Rp <?php echo number_format($menu['harga']); ?>)
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Jumlah Beli</td>
                <td><input type="number" name="jumlah" min="1" required></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="submit" value="Proses Pembayaran"></td>
            </tr>
        </table>
    </form>
</body>
</html>
