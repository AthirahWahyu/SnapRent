<?php
/**
 * SnapRent – API Autentikasi Admin
 * Endpoint : /api/auth.php
 *
 * POST  /api/auth.php?action=login   → Login admin
 * POST  /api/auth.php?action=logout  → Logout (hapus session)
 * GET   /api/auth.php?action=me      → Cek session aktif
 */

declare(strict_types=1);

// ─── Konfigurasi ────────────────────────────────────────────────────────────

const SESSION_NAME    = 'snaprent_session';
const SESSION_TIMEOUT = 3600; // detik (1 jam)

// ─── Inisialisasi ───────────────────────────────────────────────────────────

session_name(SESSION_NAME);
session_start();

header('Content-Type: application/json; charset=UTF-8');

// Hanya izinkan method GET dan POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$action = $_GET['action'] ?? '';

// ─── Router ─────────────────────────────────────────────────────────────────

match ($action) {
    'login'  => handleLogin(),
    'logout' => handleLogout(),
    'me'     => handleMe(),
    default  => (function () {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali.']);
        exit;
    })(),
};

// ════════════════════════════════════════════════════════════════════════════
// Handler Functions
// ════════════════════════════════════════════════════════════════════════════

/**
 * POST /api/auth.php?action=login
 *
 * Body (JSON) :
 *   { "username": "...", "password": "..." }
 *
 * Response sukses (200):
 *   { "success": true, "message": "...", "admin": { id_admin, username, nama_lengkap } }
 *
 * Response gagal (401 / 422 / 500):
 *   { "success": false, "message": "..." }
 */
function handleLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    // Ambil & validasi input
    $body = json_decode(file_get_contents('php://input'), true);

    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if ($username === '' || $password === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
        exit;
    }

    // Koneksi database
    $pdo = getDbConnection();

    try {
        // Cari admin berdasarkan username
        $stmt = $pdo->prepare(
            'SELECT id_admin, username, password, nama_lengkap
               FROM admin
              WHERE username = :username
              LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi password
        if (!$admin || !password_verify($password, $admin['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
            exit;
        }

        // Regenerasi session ID untuk mencegah session fixation attack
        session_regenerate_id(true);

        // Simpan data admin ke session
        $_SESSION['admin'] = [
            'id_admin'     => $admin['id_admin'],
            'username'     => $admin['username'],
            'nama_lengkap' => $admin['nama_lengkap'],
        ];
        $_SESSION['login_at'] = time();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil.',
            'admin'   => $_SESSION['admin'],
        ]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query database gagal: ' . $e->getMessage()]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * POST /api/auth.php?action=logout
 *
 * Menghapus session admin yang sedang aktif.
 *
 * Response (200):
 *   { "success": true, "message": "..." }
 */
function handleLogout(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    // Hapus semua data session
    $_SESSION = [];

    // Hapus cookie session dari browser
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);
    exit;
}

/**
 * GET /api/auth.php?action=me
 *
 * Mengecek apakah admin sudah login dan session masih aktif.
 *
 * Response terautentikasi (200):
 *   { "success": true, "admin": { id_admin, username, nama_lengkap } }
 *
 * Response tidak terautentikasi (401):
 *   { "success": false, "message": "..." }
 */
function handleMe(): void
{
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesi tidak aktif atau sudah habis.']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'admin'   => $_SESSION['admin'],
    ]);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
// Helper Functions
// ════════════════════════════════════════════════════════════════════════════

/**
 * Cek apakah admin sudah terautentikasi dan session belum timeout.
 */
function isAuthenticated(): bool
{
    if (empty($_SESSION['admin']) || empty($_SESSION['login_at'])) {
        return false;
    }

    // Cek timeout session
    if ((time() - $_SESSION['login_at']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }

    // Perbarui timestamp aktifitas terakhir
    $_SESSION['login_at'] = time();

    return true;
}

/**
 * Membuat koneksi PDO ke PostgreSQL menggunakan variabel dari .env.
 *
 * @throws RuntimeException jika koneksi gagal atau .env tidak ditemukan.
 */
function getDbConnection(): PDO
{
    // Muat .env satu level di atas public/
    $envFile = dirname(__DIR__, 2) . '/.env';

    if (!file_exists($envFile)) {
        // Fallback: coba root project langsung
        $envFile = dirname(__DIR__, 1) . '/.env';
    }

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue; // lewati komentar
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim(trim($value), "\"'");
                $_ENV[$key] = $value;
            }
        }
    }

    $host   = $_ENV['DB_HOST'] ?? 'localhost';
    $port   = $_ENV['DB_PORT'] ?? '5432';
    $dbname = $_ENV['DB_NAME'] ?? 'snaprent_db';
    $user   = $_ENV['DB_USER'] ?? 'postgres';
    $pass   = $_ENV['DB_PASS'] ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
        exit;
    }

    return $pdo;
}

