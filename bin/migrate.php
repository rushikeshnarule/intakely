<?php
/**
 * Intakely — Migration Runner
 * Plain SQL migration runner with schema_migrations tracking.
 * Connects as the migrator/owner role.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

echo "====================================================\n";
echo " Intakely — Database Migration Runner\n";
echo "====================================================\n\n";

// Connect as owner/migrator role to run DDL and RLS policies
$host = env('DB_HOST', 'localhost');
$port = (int) env('DB_PORT', '5432');
$dbname = env('DB_NAME', 'intakely');
$user = env('DB_MIGRATOR_USER', env('DB_USER', 'intakely_owner'));
$pass = env('DB_MIGRATOR_PASS', env('DB_PASS', 'intakely_owner_secret'));

try {
    $db = new Database($host, $port, $dbname, $user, $pass);
    $pdo = $db->pdo();
    echo "Connected to database '{$dbname}' on {$host}:{$port} as '{$user}'.\n\n";
} catch (\Throwable $e) {
    echo "[ERROR] Could not connect to database: " . $e->getMessage() . "\n";
    echo "Tip: Run 'docker compose up -d' first to start PostgreSQL.\n";
    exit(1);
}

// 1. Ensure schema_migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        id SERIAL PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
");

// 2. Fetch already applied migrations
$stmt = $pdo->query("SELECT filename FROM schema_migrations ORDER BY id ASC");
$applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Scan migration directory
$migrationDir = BASE_PATH . '/database/migrations';
$files = glob($migrationDir . '/*.sql');
sort($files);

$pendingCount = 0;
foreach ($files as $filePath) {
    $filename = basename($filePath);
    if (in_array($filename, $applied, true)) {
        continue;
    }

    $pendingCount++;
    echo "Migrating: {$filename}... ";

    $sql = file_get_contents($filePath);

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $logStmt = $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
        $logStmt->execute([$filename]);
        $pdo->commit();
        echo "DONE\n";
    } catch (\Throwable $e) {
        $pdo->rollBack();
        echo "FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($pendingCount === 0) {
    echo "Nothing to migrate. Schema is up to date.\n";
} else {
    echo "\nSuccessfully applied {$pendingCount} migration(s).\n";
}

echo "====================================================\n";
