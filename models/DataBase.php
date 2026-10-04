<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\AppException;
use PDO;

final class DataBase
{
    private static ?PDO $pdo = null;

    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = self::env('DB_HOST');
        $port = self::env('DB_PORT');
        $name = self::env('DB_NAME');
        $user = self::env('DB_USER');
        $password = self::env('DB_PASSWORD');
        if ($host === '' || $port === '' || $name === '' || $user === '' || $password === '') {
            throw new AppException('La configuración de la base de datos está incompleta.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        self::$pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }

    private static function env(string $key): string
    {
        $value = getenv($key);

        return is_string($value) ? $value : '';
    }
}
