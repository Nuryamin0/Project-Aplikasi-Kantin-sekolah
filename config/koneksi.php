<?php
$hostname = "sql210.infinityfree.com";
$username = "if0_43030746";
$password = "kantinsekolah0";
$dbname = "if0_43030746_kantin_sekolah";

$koneksi = mysqli_connect($hostname, $username, $password, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
