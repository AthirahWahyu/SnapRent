/**
 * SnapRent - Admin Login Handler
 * Mengelola interaksi form dan request autentikasi ke backend API.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const errorAlert = document.getElementById('error-alert');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (!form || !errorAlert || !submitBtn) {
        return;
    }

    /**
     * Menampilkan pesan error pada form
     * @param {string} message
     */
    const showError = (message) => {
        errorAlert.textContent = message;
        errorAlert.style.display = 'block';
    };

    /**
     * Menyembunyikan pesan error
     */
    const hideError = () => {
        errorAlert.textContent = '';
        errorAlert.style.display = 'none';
    };

    /**
     * Mengatur state loading tombol submit
     * @param {boolean} isLoading
     * @param {string} [loadingText='Loading...']
     */
    const setLoading = (isLoading, loadingText = 'Loading...') => {
        submitBtn.disabled = isLoading;
        if (isLoading) {
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = loadingText;
        } else {
            submitBtn.textContent = submitBtn.dataset.originalText || 'Login';
        }
    };

    /**
     * Melakukan request login ke API auth
     * @param {string} username
     * @param {string} password
     * @returns {Promise<Object>}
     */
    const requestLogin = async (username, password) => {
        const payload = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ username, password })
        };

        // Path relatif dari /pages/admin/ ke /api/
        const res = await fetch('../../api/auth.php?action=login', payload);
        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Respon API bukan JSON:', text);
            throw new Error('Terjadi kesalahan server internal (cek konfigurasi database / extension PHP).');
        }

        return data;
    };

    /**
     * Event submit form login
     */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const username = form.username.value.trim();
        const password = form.password.value;

        if (!username || !password) {
            showError('Username dan password wajib diisi.');
            return;
        }

        setLoading(true);

        try {
            const data = await requestLogin(username, password);

            if (data && data.success) {
                window.location.href = 'dashboard.php';
            } else {
                showError(data?.message || 'Login gagal. Silakan periksa kembali data Anda.');
                setLoading(false);
            }
        } catch (err) {
            console.error('Error saat login:', err);
            showError(err.message || 'Terjadi kesalahan pada sistem atau jaringan. Silakan coba lagi.');
            setLoading(false);
        }
    });
});

