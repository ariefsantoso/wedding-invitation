<?php
/**
 * API Ucapan & Doa — simpan dan ambil ucapan dari database MariaDB.
 * GET  /wishes.php  -> daftar semua ucapan (JSON)
 * POST /wishes.php  -> simpan ucapan baru (JSON)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dbHost = '127.0.0.1';
$dbName = 'wedding';
$dbUser = 'wedding_user';
$dbPass = '1NsVbHSIQreGZ9UJyjl5LMYf';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database tidak tersedia.']);
    exit;
}

function clean_text($value, $maxLen)
{
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return mb_substr($value, 0, $maxLen);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT name1, name2, message, created_at FROM wishes ORDER BY id DESC LIMIT 100');
    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'wishes' => $rows]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $name1 = clean_text($data['name1'] ?? '', 100);
    $name2 = clean_text($data['name2'] ?? '', 100);
    $message = clean_text($data['message'] ?? '', 1000);

    if ($name1 === '' || $message === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Nama dan ucapan wajib diisi.']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO wishes (name1, name2, message) VALUES (?, ?, ?)');
    $stmt->execute([$name1, $name2, $message]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method tidak diizinkan.']);
