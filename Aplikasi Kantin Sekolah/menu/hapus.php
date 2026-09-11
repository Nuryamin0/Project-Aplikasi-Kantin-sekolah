<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id = (int) ($_GET['id_menu'] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM menu WHERE id_menu = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: ../index.php");
exit();
?>