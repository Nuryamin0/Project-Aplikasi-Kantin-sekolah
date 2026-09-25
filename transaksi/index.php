<?php
include "../config/koneksi.php";

/** @var mysqli $koneksi */

// Auto-migration jika kolom catatan belum ada
$cek_catatan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_transaksi LIKE 'catatan'");
$has_catatan = ($cek_catatan && mysqli_num_rows($cek_catatan) > 0);
if (!$has_catatan) {
    @mysqli_query($koneksi, "ALTER TABLE detail_transaksi ADD COLUMN catatan TEXT NULL AFTER subtotal");
    $cek_catatan = mysqli_query($koneksi, "SHOW COLUMNS FROM detail_transaksi LIKE 'catatan'");
    $has_catatan = ($cek_catatan && mysqli_num_rows($cek_catatan) > 0);
}

$catatan_expr = $has_catatan
    ? "GROUP_CONCAT(CASE WHEN dt.catatan IS NULL OR dt.catatan = '' THEN NULL ELSE CONCAT(m.nama_menu, ': ', dt.catatan) END SEPARATOR ', ') AS catatan_pesanan"
    : "NULL AS catatan_pesanan";

$query = "
    SELECT t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar,
           GROUP_CONCAT(CONCAT(m.nama_menu, ' (x', dt.jumlah, ')') SEPARATOR ', ') AS item_dibeli,
           $catatan_expr
    FROM transaksi t
    LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN menu m ON dt.id_menu = m.id_menu
    GROUP BY t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar
    ORDER BY t.tanggal_transaksi DESC
";
$hasil = mysqli_query($koneksi, $query);
if (!$hasil) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Sekolah - Riwayat Transaksi</title>

    
    <!-- CSS Internal (Soft Aesthetic Sage Green Theme) -->
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f4f6f4;
            color: #4a5568;
            padding: 40px 15px;
        }

        /* Sub-header Aesthetic */
        .sub-tagline {
            max-width: 950px;
            margin: 0 auto 12px auto;
            color: #789078;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(107, 142, 108, 0.08);
            overflow: hidden;
            border: 1px solid #e2e8e2;
        }

        /* Header Gradasi Hijau Sage yang Lembut */
        .card-header {
            background: linear-gradient(135deg, #87a08b 0%, #6b8e6e 100%);
            color: #ffffff;
            padding: 28px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            margin-bottom: 4px;
        }

        .card-header p {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25 ease;
            cursor: pointer;
            border: none;
        }

        .btn-light {
            background-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            backdrop-filter: blur(4px);
        }

        .btn-light:hover {
            background-color: rgba(255, 255, 255, 0.35);
        }

        .btn-warning {
            background-color: #f3dfc1;
            color: #6b5335;
        }

        .btn-warning:hover {
            background-color: #ebd3b0;
        }

        .card-body {
            padding: 28px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #fafbfa;
            color: #839284;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 14px 18px;
            border-bottom: 2px solid #edf0ed;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #f2f4f2;
            font-size: 0.9rem;
            color: #4a5568;
        }

        tr {
            transition: background-color 0.2s ease;
        }

        tr:hover {
            background-color: #f7f9f7;
        }

        .badge-code {
            background-color: #eaf1eb;
            color: #4a6b4e;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.825rem;
            display: inline-block;
        }

        .total-price {
            font-weight: 700;
            color: #557558;
        }

        .action-links {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.775rem;
            border-radius: 8px;
        }

        .btn-info {
            background-color: #e3edf7;
            color: #4a6984;
        }
        .btn-info:hover { background-color: #d4e3f3; }

        .btn-edit {
            background-color: #f7eee3;
            color: #84694a;
        }
        .btn-edit:hover { background-color: #f2e3d0; }

        .btn-danger {
            background-color: #f9e8e8;
            color: #9c5252;
        }
        .btn-danger:hover { background-color: #f4d6d6; }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #a0aec0;
        }
    </style>
</head>
<body>

<div class="sub-tagline">Fresh • Sehat • Enak • Bersahabat</div>

<div class="container">
    <div class="card">
        
        <!-- Header -->
        <div class="card-header">
            <div>
                <h2>KANTIN SEHAT</h2>
                <p>Riwayat dan pemantauan transaksi kasir</p>
            </div>
            <div class="btn-group">
                <a href="../index.php" class="btn btn-light">Kembali</a>
            </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">No</th>
                            <th style="width: 13%;">Kode</th>
                            <th style="width: 18%;">Nama Pembeli</th>
                            <th style="width: 16%;">Tanggal</th>
                            <th style="width: 14%;">Total</th>
                            <th style="width: 22%;">Catatan</th>
                            <th style="width: 12%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($hasil) > 0) {
                            while($data = mysqli_fetch_assoc($hasil)) { 
                                $id_transaksi = $data['id_transaksi'];
                        ?>
                        <tr>
                            <td style="text-align: center; color: #a0aec0;"><?php echo $no++; ?></td>
                            <td><span class="badge-code"><?php echo htmlspecialchars($data['kode_transaksi']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($data['nama_pembeli']); ?></strong></td>
                            <td style="color: #718096;"><?php echo htmlspecialchars($data['tanggal_transaksi']); ?></td>
                            <td class="total-price">Rp <?php echo number_format($data['total_bayar'], 0, ',', '.'); ?></td>
                            <td><?php echo !empty($data['catatan_pesanan']) ? htmlspecialchars($data['catatan_pesanan']) : '-'; ?></td>
                            <td>
                                <div class="action-links">
                                    <a href="detail.php?id=<?php echo $id_transaksi; ?>" class="btn btn-sm btn-info" title="Detail">Detail</a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else { 
                        ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                Belum ada data transaksi.
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>