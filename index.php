<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

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
$total_user = ($cek_user) ? (mysqli_fetch_assoc($cek_user)['total'] ?? 0) : 0;
if ($total_user == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt_seed = mysqli_prepare($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', ?, 'Administrator Kantin', 'admin')");
    if ($stmt_seed) {
        mysqli_stmt_bind_param($stmt_seed, "s", $admin_pass);
        mysqli_stmt_execute($stmt_seed);
        mysqli_stmt_close($stmt_seed);
    }
}

// 1. PROSES LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
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

                header("Location: index.php");
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
            <a href="transaksi/index.php" class="btn-add" style="background-color:#16a34a;">riwayat Transaksi</a>
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
                                <?php if (!empty($menu['foto'])): ?>
                                    <img src="menu/uploads/<?= htmlspecialchars($menu['foto']); ?>" alt="Foto" class="img-thumb" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'45\' height=\'45\' viewBox=\'0 0 45 45\'%3E%3Crect width=\'45\' height=\'45\' fill=\'%23e2e8f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'20\'%3E🍽️%3C/text%3E%3C/svg%3E';">
                                <?php else: ?>
                                    <div style="width: 45px; height: 45px; background: #e2e8f0; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;">🍽️</div>
                                <?php endif; ?>
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
// 6. CABANG 3: KATALOG PEMBELI & CHECKOUT DENGAN CATATAN (Jika Role = 'user')
else :
    // Proses jika form checkout dikirim
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_pesanan'])) {$id_user = $_SESSION['id_user'];$nama_pembeli = $_SESSION['nama'] ?? 'Siswa';$kode_transaksi = 'TRX-' . time() . '-' . rand(100, 999);
        $tanggal_transaksi = date('Y-m-d H:i:s');
        
        $items_json =$_POST['cart_data'] ?? '';
        $cart_items = json_decode($items_json, true);

        if (empty($cart_items)) {
            echo "<script>alert('Keranjang belanja masih kosong!'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        }

        // Hitung total bayar
        $total_bayar = 0;
        foreach ($cart_items as $item) {$total_bayar += ($item['price'] *$item['quantity']);
        }

        mysqli_begin_transaction($koneksi);

        try {
            // 1. Simpan ke tabel transaksi 
            $stmt_tx = mysqli_prepare($koneksi, "INSERT INTO transaksi (kode_transaksi, nama_pembeli, tanggal_transaksi, total_bayar) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_tx, "sssd", $kode_transaksi,$nama_pembeli, $tanggal_transaksi,$total_bayar);
            mysqli_stmt_execute($stmt_tx);
            $id_transaksi = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_tx);

            // 2. Simpan detail transaksi (termasuk catatan/request jika kolom tabel detail_transaksi sudah ada)
            // Catatan: Pastikan tabel detail_transaksi Anda memiliki kolom 'catatan' atau 'keterangan'. Jika belum, Anda bisa menambahkannya di database.
            foreach ($cart_items as $item) {$id_menu = $item['id'];$jumlah = $item['quantity'];$subtotal = $item['price'] *$jumlah;
                $catatan =$item['note'] ?? '';

                // Query ini mengasumsikan tabel Anda memiliki kolom 'catatan'. 
                // Jika tabel detail_transaksi belum ada kolom catatan, Anda bisa menambahkannya lewat phpMyAdmin (ALTER TABLE detail_transaksi ADD catatan TEXT;).
                $stmt_dt = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_menu, jumlah, subtotal, catatan) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_dt, "iiids", $id_transaksi, $id_menu,$jumlah, $subtotal,$catatan);
                mysqli_stmt_execute($stmt_dt);
                mysqli_stmt_close($stmt_dt);

                // Kurangi stok menu
                $stmt_stok = mysqli_prepare($koneksi, "UPDATE menu SET stok = stok - ? WHERE id_menu = ?");
                mysqli_stmt_bind_param($stmt_stok, "ii", $jumlah,$id_menu);
                mysqli_stmt_execute($stmt_stok);
                mysqli_stmt_close($stmt_stok);
            }

            mysqli_commit($koneksi);
            echo "<script>alert('Pesanan berhasil dibuat atas nama " . htmlspecialchars($nama_pembeli) . "!'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal memproses pesanan: " . addslashes($e->getMessage()) . "'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        }
    }

    // Query menu
    $q_menu_user = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");
    $data_menu_user = [];
    if ($q_menu_user) {
        while ($row = mysqli_fetch_assoc($q_menu_user)) {
            $data_menu_user[] =$row;
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
    <style>
        .catalog-container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .floating-cart-btn {
            position: fixed; bottom: 30px; right: 30px; background-color: #16a34a; color: white;
            border: none; width: 65px; height: 65px; border-radius: 50%; cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25); display: flex; justify-content: center;
            align-items: center; font-size: 1.6rem; z-index: 999; transition: transform 0.2s ease;
        }
        .floating-cart-btn:hover { transform: scale(1.1); background-color: #15803d; }
        .cart-badge {
            position: absolute; top: 5px; right: 5px; background-color: #dc2626; color: white;
            font-size: 0.75rem; font-weight: bold; padding: 2px 7px; border-radius: 50px; border: 2px solid white;
        }
        .cart-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 1000; display: none; opacity: 0; transition: opacity 0.3s ease;
        }
        .cart-overlay.show { display: block; opacity: 1; }
        .cart-drawer {
            position: fixed; top: 0; right: -420px; width: 100%; max-width: 400px; height: 100%;
            background: white; z-index: 1001; box-shadow: -5px 0 25px rgba(0,0,0,0.15);
            transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;
        }
        .cart-drawer.open { right: 0; }
        .cart-drawer-header { padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .cart-drawer-body { padding: 20px; flex: 1; overflow-y: auto; }
        .cart-item-row { padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .cart-item-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .cart-item-row button { padding: 2px 8px; background: #e2e8f0; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .input-note { width: 100%; padding: 6px 8px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-top: 6px; }
        .cart-drawer-footer { padding: 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .summary-flex { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 1.1rem; color: #1e293b; }
        .btn-checkout-main { width: 100%; background-color: #16a34a; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-checkout-main:hover { background-color: #15803d; }
        .close-drawer-btn { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #64748b; }
    </style>
</head>
<body>

<body>

    <!-- Aksesoris sayuran lucu -->
    <div class="veggie-decoration veggie-carrot">🍜</div>
    <div class="veggie-decoration veggie-broccoli">🧋</div>
    <div class="veggie-decoration veggie-tomato">☕</div>
    <div class="veggie-decoration veggie-corn">🍟</div>

    <!-- POP UP WELCOME -->
<div class="welcome-popup" id="welcomePopup">
    <div class="popup-card">

        <button class="popup-close" onclick="closePopup()">×</button>

        <div class="popup-icon">🍽️</div>

        <h2>Selamat Datang! 🍚</h2>

        <p>
            Selamat datang di <b>Kantin Sehat</b>!
            Yuk pilih makanan favoritmu dan tetap sehat
        </p>

        <button class="popup-button" onclick="closePopup()">
            Yuk Jajan!
        </button>

    </div>
</div>

<script>
     function closePopup() {
    const popup = document.getElementById("welcomePopup");

    popup.style.opacity = "0";

    setTimeout(() => {
        popup.style.display = "none";
    }, 300);
    }
</script>

    <div class="header">
        <div>
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
            <p style="color: #64748b; font-size: 0.9rem; margin-top: 4px;">Akun Pembeli / Siswa</p>
        </div>
        <a href="?action=logout" class="btn-logout">Logout</a>
    </div>

    <div class="catalog-container">
        <h2 style="font-size: 1.2rem; color: #1e293b; margin-bottom: 15px;">Daftar Menu Makanan & Minuman Tersedia</h2>

        <div class="menu-grid">
            <?php if (!empty($data_menu_user)): ?>
                <?php foreach ($data_menu_user as$item): ?>
                    <div class="menu-card">
                        <img src="menu/uploads/<?= htmlspecialchars($item['foto'] ?? ''); ?>" alt="Foto" onerror="this.src='https://via.placeholder.com/220x150'">
                        <div class="card-body">
                            <div>
                                <span class="badge-kat"><?= htmlspecialchars($item['kategori']); ?></span>
                                <div class="menu-title"><?= htmlspecialchars($item['nama_menu'] ?? $item['nama_produk'] ?? ''); ?></div>
                                <div class="menu-price">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></div>
                                <small style="color: #64748b;">Sisa stok: <strong><?= $item['stok']; ?></strong></small>
                            </div>
                            <button type="button" class="btn-buy" onclick="addToCart(<?= $item['id_menu']; ?>, '<?= htmlspecialchars(addslashes($item['nama_menu'] ?? '')); ?>', <?= $item['harga']; ?>, <?=$item['stok']; ?>)">
                                + Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 40px; background: #fff; border-radius: 8px;">
                    Belum ada menu yang tersedia saat ini.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tombol Ikon Keranjang Mengambang -->
    <button type="button" class="floating-cart-btn" onclick="toggleCartDrawer()" title="Buka Keranjang">
        🛒
        <span class="cart-badge" id="cart-counter">0</span>
    </button>

    <div class="cart-overlay" id="cartOverlay" onclick="toggleCartDrawer()"></div>

    <!-- Form Drawer Keranjang -->
    <div class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h3>🛒 Keranjang Pesanan</h3>
            <button type="button" class="close-drawer-btn" onclick="toggleCartDrawer()">&times;</button>
        </div>
        
        <form method="POST" action="" id="checkoutForm" class="cart-drawer-body" style="display:flex; flex-direction:column; justify-content:space-between; height:100%; padding:20px;">
            <input type="hidden" name="checkout_pesanan" value="1">
            <input type="hidden" name="cart_data" id="cartDataInput">

            <div>
                <!-- Informasi Nama Siswa -->
                <div style="background: #f1f5f9; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                    👤 Pemesan: <strong><?= htmlspecialchars($_SESSION['nama']); ?></strong>
                </div>

                <div id="cart-items-container">
                    <p style="color: #94a3b8; text-align: center; font-size: 0.9rem; padding: 40px 0;">Keranjang masih kosong</p>
                </div>
            </div>

            <div class="cart-drawer-footer" style="padding: 0; background: transparent; border: none;">
                <div class="summary-flex">
                    <span>Total:</span>
                    <strong id="cart-total" style="color: #16a34a;">Rp 0</strong>
                </div>
                <button type="submit" class="btn-checkout-main" id="checkoutBtn" disabled style="opacity: 0.6; cursor: not-allowed;" onclick="prepareCheckout()">
                    Proses Pesanan Sekarang
                </button>
            </div>
        </form>
    </div>

    <script>
        let cart = [];

        function toggleCartDrawer() {
            document.getElementById('cartDrawer').classList.toggle('open');
            document.getElementById('cartOverlay').classList.toggle('show');
        }

        function addToCart(id, name, price, maxStock) {
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                if (existingItem.quantity < maxStock) {
                    existingItem.quantity += 1;
                } else {
                    alert("Jumlah pesanan melebihi stok yang tersedia!");
                    return;
                }
            } else {
                cart.push({ id, name, price, quantity: 1, maxStock, note: '' });
            }
            updateCartUI();
        }

        function updateQuantity(id, amount) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity += amount;
                if (item.quantity > item.maxStock) {
                    item.quantity = item.maxStock;
                    alert("Jumlah pesanan mencapai batas stok!");
                }
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            updateCartUI();
        }

        // Fungsi memperbarui catatan/request per item menu di keranjang
        function updateNote(id, value) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.note = value;
            }
        }

        function calculateTotal() {
            return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        }

        function updateCartUI() {
            const container = document.getElementById('cart-items-container');
            const totalElement = document.getElementById('cart-total');
            const counterElement = document.getElementById('cart-counter');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (!container) return;
            container.innerHTML = '';
            
            if (cart.length === 0) {
                container.innerHTML = '<p style="color: #94a3b8; text-align: center; font-size: 0.9rem; padding: 40px 0;">Keranjang masih kosong</p>';
                totalElement.innerText = 'Rp 0';
                counterElement.innerText = '0';
                checkoutBtn.disabled = true;
                checkoutBtn.style.opacity = '0.6';
                checkoutBtn.style.cursor = 'not-allowed';
                return;
            }
            
            counterElement.innerText = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            checkoutBtn.disabled = false;
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.cursor = 'pointer';
            
            cart.forEach(item => {
                const row = document.createElement('div');
                row.className = 'cart-item-row';
                row.innerHTML = `
                    <div class="cart-item-top">
                        <div style="font-size: 0.9rem;">
                            <strong>${item.name}</strong><br>
                            <small style="color: #64748b;">Rp ${item.price.toLocaleString('id-ID')} x ${item.quantity}</small>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <span style="font-size: 0.9rem; min-width: 15px; text-align: center;">${item.quantity}</span>
                            <button type="button" onclick="updateQuantity(${item.id}, 1)">+</button>
                        </div>
                    </div>
                    <div>
                        <input type="text" class="input-note" placeholder="Catatan/request (contoh: jangan pakai pedas)" value="${item.note || ''}" oninput="updateNote(${item.id}, this.value)">
                    </div>
                `;
                container.appendChild(row);
            });
            
            totalElement.innerText = `Rp ${calculateTotal().toLocaleString('id-ID')}`;
        }

        // Masukkan data keranjang beserta catatan ke dalam input tersembunyi sebelum dikirim ke database
        function prepareCheckout() {
            document.getElementById('cartDataInput').value = JSON.stringify(cart);
        }
    </script>
</body>
</html>

<?php endif; ?>