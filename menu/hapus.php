<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

$id = $_GET['id_menu'];

$query = "DELETE FROM menu WHERE id_menu = '$id'";
mysqli_query($koneksi, $query);

header("Location: index.php");
exit();
?>