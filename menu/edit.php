<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id =$_GET['id_menu'];
if (isset($_POST['submit'])) {
    $nama_produk = $_POST['nama_produk'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    $query = "UPDATE menu SET nama_produk ='$nama_produk', kategori = '$kategori', harga = '$harga', stok = '$stok' where id ='$id'";
    mysqli_query($koneksi, $query);
    header("Location:index.php");
    exit;
}
    $query_lama = "SELECT * FROM menu WHERE id_menu ='$id'";
    $hasil_lama = mysqli_query($koneksi, $query_lama);
    $lama = mysqli_fetch_assoc($hasil_lama);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Edit data menu</h1>
    <form action="" method="POST">
    <input type="text" name="nama_produk" value="<?php echo $lama['nama_produk']; ?>">
    <select name="kategori" value="<?php echo $lama['kategori']; ?>">
        <option value="">Pilih Kategori</option>
        <option value="Makanan">Makanan</option>
        <option value="Minuman">Minuman</option>
        <option value="Cemilan">Cemilan</option>
    </select>
    <input type="text" name="harga" value="<?php echo $lama['harga']; ?>">
    <input type="text" name="stok" value="<?php echo $lama['stok']; ?>">
    <button type="submit" name="submit" class="btn-zoom">Update</button>
</form>

