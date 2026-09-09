<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include __DIR__ . '/config/koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Cek config/koneksi.php");
}

// 1. PROSES LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 2. PROSES LOGIN (Jika Form Login Dikirim)
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $user = mysqli_fetch_assoc($q_user);
        
        // Verifikasi password (menggunakan password_verify atau plain text)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['id_user']  = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama_lengkap'] ?? $user['username'];
            $_SESSION['role']     = $user['role']; // Nilai: 'admin' atau 'user'

            // Refresh halaman setelah login berhasil
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $login_error = "Password salah!";
        }
    } else {
        $login_error = "Username tidak ditemukan!";
    }
}

// 3. CABANG 1: FORM LOGIN (Jika Belum Login)
if (!isset($_SESSION['role'])) :
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Kantin</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 380px; }
        .login-card h2 { margin-bottom: 20px; color: #1e293b; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; color: #475569; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
        .btn-login { width: 100%; background: #2563eb; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-login:hover { background: #1d4ed8; }
        .alert { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login Kantin</h2>
        <?php if (!empty($login_error)): ?>
            <div class="alert"><?= htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Masuk</button>
        </form>
    </div>
</body>
</html>

<?php
// 4. CABANG 2: DASHBOARD ADMIN (Jika Role = 'admin')
elseif ($_SESSION['role'] === 'admin') :

    // 1) Statistik transaksi
    $q_stat_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) AS total_tx, COALESCE(SUM(total_bayar), 0) AS total_pendapatan FROM transaksi");
    $stat_tx = mysqli_fetch_assoc($q_stat_transaksi);
    $total_transaksi = (int)($stat_tx['total_tx'] ?? 0);
    $total_pendapatan = (float)($stat_tx['total_pendapatan'] ?? 0);

    // 2) Statistik menu
    $q_stat_menu = mysqli_query($koneksi, "SELECT COUNT(*) AS total_menu, SUM(CASE WHEN stok < 5 THEN 1 ELSE 0 END) AS stok_sedikit FROM menu");
    $stat_menu = mysqli_fetch_assoc($q_stat_menu);
    $total_menu = (int)($stat_menu['total_menu'] ?? 0);
    $stok_sedikit = (int)($stat_menu['stok_sedikit'] ?? 0);

    // 3) Ambil data menu
    $q_menu = mysqli_query($koneksi, "SELECT * FROM menu ORDER BY id_menu DESC");
    $data_menu = [];
    while ($row = mysqli_fetch_assoc($q_menu)) {
        $data_menu[] = $row;
    }

    // 4) Ambil transaksi terakhir
    $query_tx = "
        SELECT t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar,
               GROUP_CONCAT(CONCAT(m.nama_menu, ' (x', dt.jumlah, ')') SEPARATOR ', ') AS item_dibeli
        FROM transaksi t
        LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
        LEFT JOIN menu m ON dt.id_menu = m.id_menu
        GROUP BY t.id_transaksi, t.kode_transaksi, t.nama_pembeli, t.tanggal_transaksi, t.total_bayar
        ORDER BY t.tanggal_transaksi DESC
        LIMIT 10
    ";
    $q_tx = mysqli_query($koneksi, $query_tx);
    $data_transaksi = [];
    while ($row = mysqli_fetch_assoc($q_tx)) {
        $data_transaksi[] = $row;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kantin</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f1f5f9; color: #334155; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header h1 { font-size: 1.8rem; color: #1e293b; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .btn-add { background-color: #2563eb; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: 500; }
        .btn-add:hover { background-color: #1d4ed8; }
        .btn-logout { background-color: #ef4444; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: 500; }
        .btn-logout:hover { background-color: #dc2626; }

        /* Grid Statistik */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #2563eb; }
        .stat-card.green { border-left-color: #16a34a; }
        .stat-card.orange { border-left-color: #f97316; }
        .stat-card.purple { border-left-color: #9333ea; }
        .stat-card h3 { font-size: 0.85rem; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .stat-card p { font-size: 1.5rem; font-weight: bold; color: #0f172a; }

        /* Layout Tabel */
        .section-title { font-size: 1.2rem; margin-bottom: 12px; color: #1e293b; }
        .table-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th { background-color: #f8fafc; color: #475569; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; font-weight: 600; }
        td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        tr:hover { background-color: #f8fafc; }
        
        .img-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; }
        .badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: 600; }
        .badge-kat { background: #e0f2fe; color: #0369a1; }
        .badge-stok { background: #fef3c7; color: #d97706; }
        
        .btn-action { text-decoration: none; font-size: 0.8rem; padding: 5px 10px; border-radius: 4px; margin-right: 3px; display: inline-block; }
        .btn-edit { background-color: #f59e0b; color: white; }
        .btn-delete { background-color: #ef4444; color: white; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Dashboard Admin Kantin</h1>
        <div class="header-actions">
            <a href="menu/tambah.php" class="btn-add">+ Tambah Menu Baru</a>
            <a href="?action=logout" class="btn-logout">Logout (<?= htmlspecialchars($_SESSION['nama']); ?>)</a>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="stats-grid">
        <div class="stat-card green">
            <h3>Total Pendapatan</h3>
            <p>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card purple">
            <h3>Total Transaksi</h3>
            <p><?= $total_transaksi; ?></p>
        </div>
        <div class="stat-card">
            <h3>Jumlah Menu</h3>
            <p><?= $total_menu; ?></p>
        </div>
        <div class="stat-card orange">
            <h3>Stok Menipis (<5)</h3>
            <p><?= $stok_sedikit; ?> Menu</p>
        </div>
    </div>

    <!-- Tabel Kelola Menu -->
    <h2 class="section-title">Daftar Menu Kantin</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data_menu)): ?>
                    <?php foreach ($data_menu as $menu): ?>
                        <tr>
                            <td>
                                <img src="../uploads/<?= htmlspecialchars($menu['foto']); ?>" alt="Foto" class="img-thumb" onerror="this.src='https://via.placeholder.com/45'">
                            </td>
                            <td><strong><?= htmlspecialchars($menu['nama_menu']); ?></strong></td>
                            <td><span class="badge badge-kat"><?= htmlspecialchars($menu['kategori']); ?></span></td>
                            <td>Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <?= $menu['stok']; ?>
                                <?php if ($menu['stok'] < 5): ?>
                                    <span class="badge badge-stok">Sedikit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_menu.php?id=<?= $menu['id_menu']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="hapus_menu.php?id=<?= $menu['id_menu']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus menu ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada data menu di database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabel Transaksi Terakhir -->
    <h2 class="section-title">10 Transaksi Terakhir</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Kode TX</th>
                    <th>Nama Pembeli</th>
                    <th>Detail Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data_transaksi)): ?>
                    <?php foreach ($data_transaksi as $tx): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($tx['kode_transaksi']); ?></strong></td>
                            <td><?= htmlspecialchars($tx['nama_pembeli']); ?></td>
                            <td><?= htmlspecialchars($tx['item_dibeli'] ?? 'Tidak ada item'); ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($tx['tanggal_transaksi'])); ?></td>
                            <td><strong>Rp <?= number_format($tx['total_bayar'], 0, ',', '.'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada data transaksi di database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>

<?php

// 5. CABANG 3: KATALOG PEMBELI (Jika Role = 'user')
else :

    // Query menu yang stoknya masih ada
    $q_menu_user = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");
    $data_menu_user = [];
    while ($row = mysqli_fetch_assoc($q_menu_user)) {
        $data_menu_user[] = $row;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Menu - Kantin</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f8fafc; color: #334155; padding: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header h1 { font-size: 1.4rem; color: #1e293b; }
        .btn-logout { background-color: #ef4444; color: #fff; text-decoration: none; padding: 8px 14px; border-radius: 6px; font-weight: 500; font-size: 0.9rem; }
        .btn-logout:hover { background-color: #dc2626; }

        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .menu-card { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; }
        .menu-card img { width: 100%; height: 150px; object-fit: cover; }
        .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .menu-title { font-size: 1.1rem; font-weight: bold; color: #0f172a; margin-bottom: 5px; }
        .menu-price { font-size: 1rem; color: #16a34a; font-weight: bold; margin-bottom: 10px; }
        .badge-kat { font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; display: inline-block; margin-bottom: 10px; width: fit-content; }
        
        .btn-buy { background-color: #2563eb; color: #fff; text-decoration: none; text-align: center; padding: 8px; border-radius: 6px; font-weight: 500; display: block; margin-top: 10px; }
        .btn-buy:hover { background-color: #1d4ed8; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
        <a href="?action=logout" class="btn-logout">Logout</a>
    </div>

    <h2 style="font-size: 1.2rem; color: #1e293b; margin-bottom: 15px;">Daftar Menu Makanan & Minuman</h2>

    <div class="menu-grid">
        <?php if (!empty($data_menu_user)): ?>
            <?php foreach ($data_menu_user as $item): ?>
                <div class="menu-card">
                    <img src="../uploads/<?= htmlspecialchars($item['foto']); ?>" alt="Foto Menu" onerror="this.src='https://via.placeholder.com/220x150'">
                    <div class="card-body">
                        <div>
                            <span class="badge-kat"><?= htmlspecialchars($item['kategori']); ?></span>
                            <div class="menu-title"><?= htmlspecialchars($item['nama_menu']); ?></div>
                            <div class="menu-price">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                        </div>
                        <a href="order.php?id=<?= $item['id_menu']; ?>" class="btn-buy">+ Pesan Sekarang</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #64748b;">Belum ada menu yang tersedia saat ini.</p>
        <?php endif; ?>
    </div>

</body>
</html>

<?php endif; ?>