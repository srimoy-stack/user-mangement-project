<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

// Load .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $db = new Database(
        $_ENV['DB_HOST'],
        (int)$_ENV['DB_PORT'],
        $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );

    echo "✔ Database connection successful!\n";

    // A simple test query
$row = $db->fetch("SELECT NOW() AS ts FROM dual");


    echo "✔ Test query executed. Time: " . $row['current_time'] . "\n";

} catch (Throwable $e) {
    echo "❌ Database connection failed:\n";
    echo $e->getMessage() . "\n";
}
