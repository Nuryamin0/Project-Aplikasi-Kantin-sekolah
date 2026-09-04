<?php
include"../config/koneksi.php";

/** @var mysqli $koneksi */

$query = "SELECT * FROM menu";
$hasil = mysqli_query($koneksi, $query);
$data = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
    <h1>Menu</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama produk</th>
                <th>kategori</th>
                <th>harga</th>
                <th>stok</th>
                <th>aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $menu):?>
            <tr>
                <td><?php echo $menu['id_menu']; ?></td>
                <td><?php echo $menu['nama_produk']; ?></td>
                <td><?php echo $menu['kategori']; ?></td>
                <td><?php echo $menu['harga'];?></td>
                <td><?php echo $menu['stok'];?></td>
                <td>
                    <a href="edit.php?id_menu=<?php echo $menu['id_menu']; ?>">Edit</a>
                    <a href="hapus.php?id_menu=<?php echo $menu['id_menu']; ?>">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="tambah.php">Tambah Menu</a>
</body>
</html>