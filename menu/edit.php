<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id = $_GET['id_menu'];

if (isset($_POST['submit'])) {
    $nama_produk = $_POST['nama_produk'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $foto = $_FILES['foto']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["foto"]["name"]);

    $query = "UPDATE menu SET nama_produk = '$nama_produk', kategori = '$kategori', harga = '$harga', stok = '$stok', foto = '$foto' WHERE id_menu = '$id'";
    $update = mysqli_query($koneksi, $query);

    if ($update) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Gagal mengupdate data: " . mysqli_error($koneksi) . "');</script>";
    }
}

$query_lama = "SELECT * FROM menu WHERE id_menu = '$id'";
$hasil_lama = mysqli_query($koneksi, $query_lama);
$lama = mysqli_fetch_assoc($hasil_lama);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Menu</title>
</head>
<body>
    <h2>Edit Data Menu</h2>
    <form action="edit.php?id_menu=<?php echo $id; ?>" method="POST">
        <table>
            <tr>
                <td>Nama Produk</td>
                <td><input type="text" name="nama_produk" value="<?php echo htmlspecialchars($lama['nama_produk']); ?>" required></td>
            </tr>
            <tr>
                <td>Kategori</td>
                <td>
                    <select name="kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Makanan" <?php echo ($lama['kategori'] == 'Makanan') ? 'selected' : ''; ?>>Makanan</option>
                        <option value="Minuman" <?php echo ($lama['kategori'] == 'Minuman') ? 'selected' : ''; ?>>Minuman</option>
                        <option value="Cemilan" <?php echo ($lama['kategori'] == 'Cemilan') ? 'selected' : ''; ?>>Cemilan</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Harga</td>
                <td><input type="number" name="harga" value="<?php echo htmlspecialchars($lama['harga']); ?>" required></td>
            </tr>
            <tr>
                <td>Stok</td>
                <td><input type="number" name="stok" value="<?php echo htmlspecialchars($lama['stok']); ?>" required></td>
            </tr>
            <tr>
                <td>Foto</td>
                <td><input type="file" name="foto" value="<?php echo htmlspecialchars($lama['foto']); ?>" required></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" name="submit">Update</button>
                    <a href="index.php">Batal</a>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
