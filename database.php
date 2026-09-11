<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $server = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $connection = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS enquiries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            service VARCHAR(120) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            feedback TEXT NOT NULL,
            is_approved TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB'
    );
    $ratingColumns = $connection->query("SHOW COLUMNS FROM ratings LIKE 'is_approved'")->fetch();
    if ($ratingColumns === false) {
        $connection->exec('ALTER TABLE ratings ADD is_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER feedback');
    }
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(120) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $adminUser = $connection->prepare('SELECT id FROM admin_users WHERE username = :username LIMIT 1');
    $adminUser->execute(['username' => ADMIN_USERNAME]);
    if ($adminUser->fetch() === false) {
        $seedAdmin = $connection->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)');
        $seedAdmin->execute([
            'username' => ADMIN_USERNAME,
            'password_hash' => password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT),
        ]);
    }

    return $connection;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
