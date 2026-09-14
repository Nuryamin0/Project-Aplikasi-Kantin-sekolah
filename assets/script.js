/**
 * Script Aplikasi Kantin Sekolah
 */

// Fungsi untuk beralih antara Tab Login dan Tab Registrasi
function switchAuthTab(tabName) {
    const loginBtn = document.getElementById('tab-login-btn');
    const regBtn = document.getElementById('tab-register-btn');
    const loginPanel = document.getElementById('login-panel');
    const regPanel = document.getElementById('register-panel');

    if (!loginBtn || !regBtn || !loginPanel || !regPanel) return;

    if (tabName === 'register') {
        loginBtn.classList.remove('active');
        regBtn.classList.add('active');
        loginPanel.classList.remove('active');
        regPanel.classList.add('active');
        const regNama = document.getElementById('reg-nama');
        if (regNama) regNama.focus();
    } else {
        regBtn.classList.remove('active');
        loginBtn.classList.add('active');
        regPanel.classList.remove('active');
        loginPanel.classList.add('active');
        const logUser = document.getElementById('login-username');
        if (logUser) logUser.focus();
    }
}

// Fungsi untuk toggle intip (lihat/sembunyikan) password
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

// Fungsi konfirmasi hapus data menu
function deleteData(id) {
    if (confirm("Apakah kamu yakin ingin menghapus data ini?")) {
        window.location.href = "hapus.php?id_menu=" + id;
    }
}