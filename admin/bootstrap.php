<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/config.php';

function admin_ensure_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS `admins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `full_name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $conn->query($sql);
}

function admin_ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    $table_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $result = $conn->query("SHOW COLUMNS FROM `{$table_safe}` LIKE '{$column_safe}'");
    if ($result && $result->num_rows > 0) {
        return;
    }

    $conn->query("ALTER TABLE `{$table_safe}` ADD COLUMN `{$column_safe}` {$definition}");
}

function admin_require_login(): void
{
    $is_admin = isset($_SESSION['role'], $_SESSION['admin_id']) && $_SESSION['role'] === 'admin';
    if ($is_admin) {
        return;
    }

    $next = basename((string)($_SERVER['PHP_SELF'] ?? 'index.php'));
    header('Location: login.php?next=' . urlencode($next));
    exit();
}

function admin_normalize_text(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function admin_user_password_is_valid(string $password): bool
{
    return (bool) preg_match('/^\d{8}$/', $password);
}

function admin_admin_password_is_valid(string $password): bool
{
    // Admin password can be 8-20 digits (seed uses 10 digits).
    return (bool) preg_match('/^\d{8,20}$/', $password);
}

function admin_bind_params(mysqli_stmt $stmt, string $types, array $values): void
{
    $refs = [];
    foreach ($values as $k => $v) {
        $refs[$k] = &$values[$k];
    }
    $params = array_merge([$types], $refs);
    if (!call_user_func_array([$stmt, 'bind_param'], $params)) {
        throw new RuntimeException('Failed to bind parameters.');
    }
}

function admin_upload_image(string $field_name, string $target_dir): ?string
{
    if (empty($_FILES[$field_name]['name']) || !is_uploaded_file($_FILES[$field_name]['tmp_name'])) {
        return null;
    }

    $extension = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    $abs_dir = rtrim(str_replace('\\', '/', __DIR__ . '/../' . $target_dir), '/');
    if (!is_dir($abs_dir) && !mkdir($abs_dir, 0777, true) && !is_dir($abs_dir)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $filename = uniqid('upload_', true) . '.' . $extension;
    $relative_path = rtrim(str_replace('\\', '/', $target_dir), '/') . '/' . $filename;
    $abs_path = $abs_dir . '/' . $filename;

    if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $abs_path)) {
        throw new RuntimeException('Could not upload file.');
    }

    return $relative_path;
}

admin_ensure_table($conn);
// Keep local/older DB dumps compatible with the admin screens.
admin_ensure_column($conn, 'doctors', 'address', "TEXT NULL AFTER `phone`");
admin_ensure_column($conn, 'patients', 'address', "TEXT NULL AFTER `phone`");
admin_ensure_column($conn, 'patients', 'age', "INT NULL AFTER `phone`");
admin_ensure_column($conn, 'patients', 'gender', "VARCHAR(20) NULL AFTER `age`");
admin_ensure_column($conn, 'doctors', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `clinic`");

// Seed first admin (if none exists).
$seed_count = $conn->query('SELECT COUNT(*) AS c FROM admins');
$seed_row = $seed_count ? $seed_count->fetch_assoc() : null;
$has_admin = (int)($seed_row['c'] ?? 0) > 0;
if (!$has_admin) {
    $seed_email = TELEMED2_ADMIN_SEED_EMAIL;
    $seed_password = TELEMED2_ADMIN_SEED_PASSWORD;
    $seed_name = TELEMED2_ADMIN_SEED_NAME;

    if (filter_var($seed_email, FILTER_VALIDATE_EMAIL) && admin_admin_password_is_valid($seed_password)) {
        $hash = password_hash($seed_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO admins (full_name, email, password) VALUES (?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sss', $seed_name, $seed_email, $hash);
            $stmt->execute();
        }
    }
}
