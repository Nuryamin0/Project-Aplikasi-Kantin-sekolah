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
    <style>
        body {
    padding: 30px;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9f9f9;
    color: #333;
}

h1 {
    color: #0d47a1;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2em;
}

a {
    text-decoration: none;
    color: #1976d2;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background-color 0.2s;
}

a:hover {
    background-color: #e3f2fd;
    color: #1565c0;
}


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

        .btn-zoom {
    display: inline-block;
    padding: 12px 24px;
    background-color: #2196f3;
    color: white;
    border-radius: 8px;
    font-weight: bold;
    text-align: center;
    margin-top: 20px;
    transition: transform 0.2s ease;
}

.btn-zoom:hover {
    transform: scale(1.1);  /* sedikit membesar saat diklik */
}

tbody {
    color: #333;
    
}
    </style>
</head>
<body>
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
                    <a href="javascript:void(0)" onclick="deleteData(<?php echo $menu['id_menu']; ?>)">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="tambah.php" class="btn-zoom">Tambah Menu</a>

    <script src="../assets/script.js"></script>
</body>
</html>