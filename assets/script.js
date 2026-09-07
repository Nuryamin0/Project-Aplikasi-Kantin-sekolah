function deleteData(id) {
    if (confirm("Apakah kamu yakin ingin menghapus data ini?")) {
        window.location.href = "hapus.php?id_menu=" + id;
    }
}