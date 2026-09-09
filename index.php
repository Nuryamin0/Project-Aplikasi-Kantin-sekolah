<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include __DIR__ . '/config/koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Cek config/koneksi.php");
}

// 0. AUTO-MIGRATION: Pastikan tabel `users` tersedia di database
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Buat akun admin awal jika tabel users masih kosong
$cek_user = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users");
$total_user = mysqli_fetch_assoc($cek_user)['total'] ?? 0;
if ($total_user == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt_seed = mysqli_prepare($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', ?, 'Administrator Kantin', 'admin')");
    mysqli_stmt_bind_param($stmt_seed, "s", $admin_pass);
    mysqli_stmt_execute($stmt_seed);
    mysqli_stmt_close($stmt_seed);
}

// 1. PROSES LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$login_error = '';
$register_error = '';
$register_success = '';
$active_tab = 'login'; // Tab default: 'login' atau 'register'

// 2. PROSES REGISTRASI (Jika Form Register Dikirim)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $active_tab = 'register';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = (isset($_POST['role']) && in_array($_POST['role'], ['admin', 'user'])) ? $_POST['role'] : 'user';

    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($confirm_password)) {
        $register_error = "Semua kolom wajib diisi!";
    } elseif (strlen($username) < 3) {
        $register_error = "Username minimal harus 3 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $register_error = "Username hanya boleh huruf, angka, dan underscore (_)!";
    } elseif (strlen($password) < 4) {
        $register_error = "Password minimal harus 4 karakter!";
    } elseif ($password !== $confirm_password) {
        $register_error = "Konfirmasi password tidak sesuai!";
    } else {
        // Cek duplikasi username
        $stmt_check = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $register_error = "Username '<strong>" . htmlspecialchars($username) . "</strong>' sudah terdaftar. Silakan pilih username lain.";
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "ssss", $nama_lengkap, $username, $hashed_password, $role);

            if (mysqli_stmt_execute($stmt_insert)) {
                $register_success = "Pendaftaran akun berhasil! Silakan masuk menggunakan username dan password Anda.";
                $active_tab = 'login';
            } else {
                $register_error = "Terjadi kesalahan saat mendaftar: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
}

// 3. PROSES LOGIN (Jika Form Login Dikirim)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $active_tab = 'login';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = "Username dan password wajib diisi!";
    } else {
        $stmt_login = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_login, "s", $username);
        mysqli_stmt_execute($stmt_login);
        $result = mysqli_stmt_get_result($stmt_login);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Verifikasi password (password_verify hash atau plain fallback)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['id_user']  = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama_lengkap'] ?? $user['username'];
                $_SESSION['role']     = $user['role']; // 'admin' atau 'user'

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $login_error = "Password yang Anda masukkan salah!";
            }
        } else {
            $login_error = "Username tidak ditemukan dalam sistem!";
        }
        mysqli_stmt_close($stmt_login);
    }
}

// 4. CABANG 1: FORM LOGIN & REGISTER (Jika Belum Login)
if (!isset($_SESSION['role'])) :
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registrasi - Aplikasi Kantin Sekolah</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= time(); ?>">
</head>
<body class="login-body">
    <div class="auth-container">
        <!-- Header / Logo Brand -->
        <div class="auth-header">
            <div class="auth-logo">🍔</div>
            <h2>Kantin Sekolah</h2>
            <p class="auth-subtitle">Sistem Manajemen & Pemesanan Kantin</p>
        </div>

        <div class="login-card">
            <!-- Navigasi Tab Login vs Register -->
            <div class="auth-tabs">
                <button type="button" class="tab-btn <?= ($active_tab === 'login') ? 'active' : ''; ?>" id="tab-login-btn" onclick="switchAuthTab('login')">
                    Masuk
                </button>
                <button type="button" class="tab-btn <?= ($active_tab === 'register') ? 'active' : ''; ?>" id="tab-register-btn" onclick="switchAuthTab('register')">
                    Daftar Akun
                </button>
            </div>

            <!-- Pesan Alert / Feedback -->
            <?php if (!empty($register_success)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <div><?= $register_success; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger" id="login-alert">
                    <span class="alert-icon">⚠️</span>
                    <div><?= $login_error; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($register_error)): ?>
                <div class="alert alert-danger" id="register-alert">
                    <span class="alert-icon">⚠️</span>
                    <div><?= $register_error; ?></div>
                </div>
            <?php endif; ?>

            <!-- PANEL 1: FORM LOGIN -->
            <div class="auth-panel <?= ($active_tab === 'login') ? 'active' : ''; ?>" id="login-panel">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <div class="input-with-icon">
                            <span class="input-icon">👤</span>
                            <input type="text" id="login-username" name="username" placeholder="Masukkan username" required autocomplete="username" value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['username'] ?? '') : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="login-password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-pwd" onclick="togglePassword('login-password', this)" title="Tampilkan/Sembunyikan Password">👁️</button>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn-primary">
                        Masuk ke Akun
                    </button>
                </form>

                <div class="auth-footer">
                    <span>Belum punya akun?</span>
                    <a href="javascript:void(0)" onclick="switchAuthTab('register')" class="auth-link">Daftar sekarang</a>
                </div>
            </div>

            <!-- PANEL 2: FORM REGISTRASI -->
            <div class="auth-panel <?= ($active_tab === 'register') ? 'active' : ''; ?>" id="register-panel">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reg-nama">Nama Lengkap</label>
                        <div class="input-with-icon">
                            <span class="input-icon">📝</span>
                            <input type="text" id="reg-nama" name="nama_lengkap" placeholder="Contoh: Budi Santoso" required autocomplete="name" value="<?= isset($_POST['register']) ? htmlspecialchars($_POST['nama_lengkap'] ?? '') : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-username">Username</label>
                        <div class="input-with-icon">
                            <span class="input-icon">👤</span>
                            <input type="text" id="reg-username" name="username" placeholder="Huruf / angka, contoh: budi123" required autocomplete="username" value="<?= isset($_POST['register']) ? htmlspecialchars($_POST['username'] ?? '') : ''; ?>">
                        </div>
                        <small class="form-hint">Gunakan huruf, angka, atau underscore (_) tanpa spasi.</small>
                    </div>

                    <div class="form-group">
                        <label>Daftar Sebagai (Peran)</label>
                        <div class="role-selector">
                            <label class="role-option">
                                <input type="radio" name="role" value="user" <?= (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : ''; ?>>
                                <span class="role-card">
                                    <span class="role-title">🛍️ Pembeli / Siswa</span>
                                    <span class="role-desc">Pesan makanan & lihat menu</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="reg-password" name="password" placeholder="Minimal 4 karakter" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pwd" onclick="togglePassword('reg-password', this)" title="Tampilkan/Sembunyikan Password">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-confirm">Konfirmasi Password</label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔐</span>
                            <input type="password" id="reg-confirm" name="confirm_password" placeholder="Ulangi password Anda" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pwd" onclick="togglePassword('reg-confirm', this)" title="Tampilkan/Sembunyikan Password">👁️</button>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn-primary btn-success-action">
                        Daftar Sekarang
                    </button>
                </form>

                <div class="auth-footer">
                    <span>Sudah memiliki akun?</span>
                    <a href="javascript:void(0)" onclick="switchAuthTab('login')" class="auth-link">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/script.js?v=<?= time(); ?>"></script>
</body>
</html>

<?php
// 5. CABANG 2: DASHBOARD ADMIN (Jika Role = 'admin')
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
    <link rel="stylesheet" href="assets/style.css?v=<?= time(); ?>">
</head>
<body>

    <div class="header">
        <h1>Dashboard Admin Kantin</h1>
        <div class="header-actions">
            <a href="menu/tambah.php" class="btn-add">+ Tambah Menu Baru</a>
            <a href="transaksi/tambah.php" class="btn-add" style="background-color:#16a34a;">+ Kasir Transaksi</a>
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
                                <img src="menu/uploads/<?= htmlspecialchars($menu['foto'] ?? ''); ?>" alt="Foto" class="img-thumb" onerror="this.src='https://via.placeholder.com/45'">
                            </td>
                            <td><strong><?= htmlspecialchars($menu['nama_menu'] ?? $menu['nama_produk'] ?? ''); ?></strong></td>
                            <td><span class="badge badge-kat"><?= htmlspecialchars($menu['kategori'] ?? ''); ?></span></td>
                            <td>Rp <?= number_format($menu['harga'] ?? 0, 0, ',', '.'); ?></td>
                            <td>
                                <?= $menu['stok'] ?? 0; ?>
                                <?php if (($menu['stok'] ?? 0) < 5): ?>
                                    <span class="badge badge-stok">Sedikit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="menu/edit.php?id_menu=<?= $menu['id_menu']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="menu/hapus.php?id_menu=<?= $menu['id_menu']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus menu ini?')">Hapus</a>
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
// 6. CABANG 3: KATALOG PEMBELI (Jika Role = 'user')
else :

    // Query menu yang stoknya masih ada
    $q_menu_user = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");
    $data_menu_user = [];
    if ($q_menu_user) {
        while ($row = mysqli_fetch_assoc($q_menu_user)) {
            $data_menu_user[] = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Menu - Kantin Sekolah</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= time(); ?>">
</head>
<body>

    <div class="header">
        <div>
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
            <p style="color: #64748b; font-size: 0.9rem; margin-top: 4px;">Akun Pembeli / Siswa</p>
        </div>
        <a href="?action=logout" class="btn-logout">Logout</a>
    </div>

    <h2 style="font-size: 1.2rem; color: #1e293b; margin-bottom: 15px;">Daftar Menu Makanan & Minuman Tersedia</h2>

    <div class="menu-grid">
        <?php if (!empty($data_menu_user)): ?>
            <?php foreach ($data_menu_user as $item): ?>
                <div class="menu-card">
                    <img src="menu/uploads/<?= htmlspecialchars($item['foto'] ?? ''); ?>" alt="Foto Menu" onerror="this.src='https://via.placeholder.com/220x150'">
                    <div class="card-body">
                        <div>
                            <span class="badge-kat"><?= htmlspecialchars($item['kategori']); ?></span>
                            <div class="menu-title"><?= htmlspecialchars($item['nama_menu'] ?? $item['nama_produk'] ?? ''); ?></div>
                            <div class="menu-price">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                            <small style="color: #64748b;">Sisa stok: <strong><?= $item['stok']; ?></strong></small>
                        </div>
                        <a href="transaksi/tambah.php" class="btn-buy">+ Pesan Menu</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 40px; background: #fff; border-radius: 8px;">
                Belum ada menu yang tersedia saat ini.
            </p>
        <?php endif; ?>
    </div>

</body>
</html>

<?php endif; ?>