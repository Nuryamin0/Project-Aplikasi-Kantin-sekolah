<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include __DIR__ . '/config/koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Cek config/koneksi.php");
}

// ------------------------------------------------------------------
// 1. PROSES SIGN OUT / LOGOUT
// ------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ------------------------------------------------------------------
// 2. PROSES SIGN IN / LOGIN
// ------------------------------------------------------------------
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $user = mysqli_fetch_assoc($q_user);
        
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['id_user']  = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama_lengkap'] ?? $user['username'];
            $_SESSION['role']     = $user['role'];

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $login_error = "Password yang Anda masukkan salah!";
        }
    } else {
        $login_error = "Username tidak ditemukan dalam sistem!";
    }
}

// ------------------------------------------------------------------
// 3. PROSES REGISTER / SIGN UP
// ------------------------------------------------------------------
$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['reg_username']));
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['reg_nama']));
    $password     = $_POST['reg_password'];
    $role         = 'user'; 

    $check_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_user) > 0) {
        $register_error = "Username sudah digunakan, silakan pilih yang lain!";
    } else {
        $query_reg = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        if (mysqli_query($koneksi, $query_reg)) {
            $register_success = "Akun berhasil dibuat! Silakan Sign In.";
        } else {
            $register_error = "Gagal mendaftar, silakan coba lagi.";
        }
    }
}

// ------------------------------------------------------------------
// 4. CABANG 1: FORM SIGN IN & REGISTER
// ------------------------------------------------------------------
if (!isset($_SESSION['role'])) :
    $active_tab = isset($_POST['register']) || !empty($register_error) ? 'signup' : 'login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In / Register - E-Kantin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        body { 
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            color: #1e293b;
        }
        .login-card { 
            background: #ffffff; 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(22, 101, 52, 0.1), 0 8px 10px -6px rgba(22, 101, 52, 0.1); 
            width: 100%; 
            max-width: 420px; 
            border: 1px solid #bbf7d0;
        }
        .brand-logo { text-align: center; margin-bottom: 24px; }
        .brand-logo h2 { font-size: 1.5rem; color: #166534; font-weight: 700; }
        .brand-logo p { font-size: 0.85rem; color: #64748b; margin-top: 4px; }
        
        .auth-tabs { display: flex; margin-bottom: 24px; background: #f8fafc; padding: 4px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .tab-btn { flex: 1; padding: 10px; background: none; border: none; font-size: 0.9rem; font-weight: 600; color: #64748b; cursor: pointer; border-radius: 8px; transition: all 0.3s ease; }
        .tab-btn.active { background: #16a34a; color: #ffffff; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2); }
        
        .auth-form { display: none; }
        .auth-form.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: #334155; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; outline: none; transition: all 0.2s; background: #f8fafc; }
        .form-group input:focus { border-color: #16a34a; background: #fff; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15); }
        
        .btn-submit { width: 100%; background: #16a34a; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2); }
        .btn-submit:hover { background: #15803d; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: 0.85rem; font-weight: 500; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-logo">
            <h2>🌿 E-Kantin Sehat</h2>
            <p>Silakan masuk atau daftar untuk mulai memesan</p>
        </div>
        
        <div class="auth-tabs">
            <button class="tab-btn <?= $active_tab === 'login' ? 'active' : ''; ?>" id="btn-login" onclick="switchTab('login')">Sign In</button>
            <button class="tab-btn <?= $active_tab === 'signup' ? 'active' : ''; ?>" id="btn-signup" onclick="switchTab('signup')">Register</button>
        </div>

        <!-- FORM SIGN IN -->
        <div id="login-form" class="auth-form <?= $active_tab === 'login' ? 'active' : ''; ?>">
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <?php if (!empty($register_success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($register_success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
            </form>
        </div>

        <!-- FORM REGISTER -->
        <div id="signup-form" class="auth-form <?= $active_tab === 'signup' ? 'active' : ''; ?>">
            <?php if (!empty($register_error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($register_error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="reg_nama" placeholder="Contoh: Budi Santoso" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="reg_username" placeholder="Buat username unik" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="reg_password" placeholder="Buat password aman" required>
                </div>
                <button type="submit" name="register" class="btn-submit">Daftar Akun</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.auth-form').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            if (tabName === 'login') {
                document.getElementById('login-form').classList.add('active');
                document.getElementById('btn-login').classList.add('active');
            } else {
                document.getElementById('signup-form').classList.add('active');
                document.getElementById('btn-signup').classList.add('active');
            }
        }
    </script>
</body>
</html>

<?php
// ------------------------------------------------------------------
// 5. CABANG 2: DASHBOARD ADMIN
// ------------------------------------------------------------------
elseif ($_SESSION['role'] === 'admin') :

    $q_stat_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) AS total_tx, COALESCE(SUM(total_bayar), 0) AS total_pendapatan FROM transaksi");
    $stat_tx = mysqli_fetch_assoc($q_stat_transaksi);
    $total_transaksi = (int)($stat_tx['total_tx'] ?? 0);
    $total_pendapatan = (float)($stat_tx['total_pendapatan'] ?? 0);

    $q_stat_menu = mysqli_query($koneksi, "SELECT COUNT(*) AS total_menu, SUM(CASE WHEN stok < 5 THEN 1 ELSE 0 END) AS stok_sedikit FROM menu");
    $stat_menu = mysqli_fetch_assoc($q_stat_menu);
    $total_menu = (int)($stat_menu['total_menu'] ?? 0);
    $stok_sedikit = (int)($stat_menu['stok_sedikit'] ?? 0);

    $q_menu = mysqli_query($koneksi, "SELECT * FROM menu ORDER BY id_menu DESC");
    $data_menu = [];
    while ($row = mysqli_fetch_assoc($q_menu)) { $data_menu[] = $row; }

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
    while ($row = mysqli_fetch_assoc($q_tx)) { $data_transaksi[] = $row; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-Kantin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        body { background-color: #f8fafc; color: #334155; padding: 30px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #ffffff; padding: 20px 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .header h1 { font-size: 1.5rem; color: #166534; font-weight: 700; }
        .header-actions { display: flex; gap: 12px; align-items: center; }
        
        .btn { text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-add { background-color: #16a34a; color: #fff; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2); }
        .btn-add:hover { background-color: #15803d; }
        .btn-logout { background-color: #fee2e2; color: #991b1b; }
        .btn-logout:hover { background-color: #fecaca; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: #fff; padding: 22px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #16a34a; }
        .stat-card.purple::before { background: #7c3aed; }
        .stat-card.orange::before { background: #ea580c; }
        .stat-card.blue::before { background: #0284c7; }
        
        .stat-card h3 { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 700; }
        .stat-card p { font-size: 1.6rem; font-weight: 700; color: #0f172a; }

        .section-title { font-size: 1.15rem; margin-bottom: 15px; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow-x: auto; margin-bottom: 35px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
        th { background-color: #f8fafc; color: #475569; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; }
        
        .img-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; display: inline-block; }
        .badge-kat { background: #dcfce7; color: #166534; }
        .badge-stok { background: #ffedd5; color: #c2410c; margin-left: 6px; }
        
        .btn-action { text-decoration: none; font-size: 0.8rem; padding: 6px 12px; border-radius: 6px; font-weight: 600; margin-right: 4px; display: inline-block; transition: opacity 0.2s; }
        .btn-edit { background-color: #fef08a; color: #854d0e; }
        .btn-edit:hover { background-color: #fde047; }
        .btn-delete { background-color: #fee2e2; color: #991b1b; }
        .btn-delete:hover { background-color: #fecaca; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🌿 Dashboard Admin Kantin</h1>
        <div class="header-actions">
            <a href="menu/tambah.php" class="btn btn-add">+ Tambah Menu Baru</a>
            <a href="?action=logout" class="btn btn-logout">Sign Out (<?= htmlspecialchars($_SESSION['nama']); ?>)</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Pendapatan</h3>
            <p style="color: #16a34a;">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card purple">
            <h3>Total Transaksi</h3>
            <p><?= $total_transaksi; ?></p>
        </div>
        <div class="stat-card blue">
            <h3>Jumlah Menu</h3>
            <p><?= $total_menu; ?></p>
        </div>
        <div class="stat-card orange">
            <h3>Stok Menipis (<5)</h3>
            <p><?= $stok_sedikit; ?> Menu</p>
        </div>
    </div>

    <h2 class="section-title">📋 Daftar Menu Kantin</h2>
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
                <?php if (!empty($data_menu)): foreach ($data_menu as $menu): ?>
                    <tr>
                        <td><img src="../uploads/<?= htmlspecialchars($menu['foto']); ?>" alt="Foto" class="img-thumb" onerror="this.src='https://via.placeholder.com/48'"></td>
                        <td><strong style="color: #0f172a;"><?= htmlspecialchars($menu['nama_menu']); ?></strong></td>
                        <td><span class="badge badge-kat"><?= htmlspecialchars($menu['kategori']); ?></span></td>
                        <td><strong>Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></strong></td>
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
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center; color: #64748b; padding: 30px;">Belum ada data menu di database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-title">⚡ 10 Transaksi Terakhir</h2>
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
                <?php if (!empty($data_transaksi)): foreach ($data_transaksi as $tx): ?>
                    <tr>
                        <td><strong style="color: #16a34a;"><?= htmlspecialchars($tx['kode_transaksi']); ?></strong></td>
                        <td><?= htmlspecialchars($tx['nama_pembeli']); ?></td>
                        <td><?= htmlspecialchars($tx['item_dibeli'] ?? 'Tidak ada item'); ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($tx['tanggal_transaksi'])); ?></td>
                        <td><strong style="color: #0f172a;">Rp <?= number_format($tx['total_bayar'], 0, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; color: #64748b; padding: 30px;">Belum ada data transaksi di database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>

<?php
// ------------------------------------------------------------------
// 6. CABANG 3: KATALOG PEMBELI (USER)
// ------------------------------------------------------------------
else :

    $q_menu_user = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");
    $data_menu_user = [];
    while ($row = mysqli_fetch_assoc($q_menu_user)) { $data_menu_user[] = $row; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Menu - E-Kantin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        body { background-color: #f8fafc; color: #334155; padding: 30px; }
        
        .user-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .user-header h1 { font-size: 1.4rem; color: #166534; font-weight: 700; }
        
        .btn-logout { background-color: #fee2e2; color: #991b1b; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: background 0.2s; }
        .btn-logout:hover { background-color: #fecaca; }
        
        .catalog-title { font-size: 1.25rem; color: #1e293b; margin-bottom: 20px; font-weight: 700; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .menu-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        
        .menu-card img { width: 100%; height: 160px; object-fit: cover; }
        .card-body { padding: 18px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        
        .badge-kat { background: #dcfce7; color: #166534; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; display: inline-block; margin-bottom: 8px; }
        .menu-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .menu-price { font-size: 1.05rem; color: #16a34a; font-weight: 700; margin-bottom: 15px; }
        
        .btn-buy { background-color: #16a34a; color: #fff; text-decoration: none; text-align: center; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.875rem; display: block; transition: background 0.2s; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2); }
        .btn-buy:hover { background-color: #15803d; }
    </style>
</head>
<body>

    <div class="user-header">
        <h1>👋 Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
        <a href="?action=logout" class="btn-logout">Sign Out</a>
    </div>

    <h2 class="catalog-title">🍽️ Daftar Menu Makanan & Minuman Tersedia</h2>

    <div class="menu-grid">
        <?php if (!empty($data_menu_user)): foreach ($data_menu_user as $item): ?>
            <div class="menu-card">
                <img src="../uploads/<?= htmlspecialchars($item['foto']); ?>" alt="Foto Menu" onerror="this.src='https://via.placeholder.com/240x160'">
                <div class="card-body">
                    <div>
                        <span class="badge-kat"><?= htmlspecialchars($item['kategori']); ?></span>
                        <div class="menu-title"><?= htmlspecialchars($item['nama_menu']); ?></div>
                        <div class="menu-price">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                    </div>
                    <a href="order.php?id=<?= $item['id_menu']; ?>" class="btn-buy">+ Pesan Sekarang</a>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 40px;">Belum ada menu yang tersedia saat ini.</p>
        <?php endif; ?>
    </div>

</body>
</html>

<?php endif; ?>