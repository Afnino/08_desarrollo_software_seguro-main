<?php

declare(strict_types=1);

namespace App\Security;

final class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900;

    public static function blocked(string $email): bool
    {
        $data = self::read($email);
        if ($data === null) {
            return false;
        }
        if ($data['count'] >= self::MAX_ATTEMPTS && $data['until'] > time()) {
            return true;
        }
        if ($data['count'] >= self::MAX_ATTEMPTS && $data['until'] <= time()) {
            self::clear($email);
        }

        return false;
    }

    public static function fail(string $email): void
    {
        $data = self::read($email) ?? ['count' => 0, 'until' => 0];
        if ($data['until'] !== 0 && $data['until'] <= time()) {
            $data = ['count' => 0, 'until' => 0];
        }
        $data['count']++;
        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $data['until'] = time() + self::LOCK_SECONDS;
        }
        self::write($email, $data);
    }

    public static function clear(string $email): void
    {
        $path = self::path($email);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private static function read(string $email): ?array
    {
        $path = self::path($email);
        $data = null;
        if (is_file($path)) {
            $raw = file_get_contents($path);
            $decoded = $raw === false ? null : json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['count'], $decoded['until'])) {
                $data = ['count' => (int) $decoded['count'], 'until' => (int) $decoded['until']];
            }
        }

        return $data;
    }

    private static function write(string $email, array $data): void
    {
        file_put_contents(self::path($email), json_encode($data), LOCK_EX);
    }

    private static function path(string $email): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
        $key = hash('sha256', mb_strtolower(trim($email), 'UTF-8') . '|' . $ip);

        return self::directory() . '/' . $key . '.json';
    }

    private static function directory(): string
    {
        $configured = getenv('LOGIN_ATTEMPT_DIR');
        $dir = is_string($configured) && $configured !== ''
            ? $configured
            : dirname(__DIR__) . '/storage/attempts';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new AppException('No se pudo preparar el control de intentos de acceso.');
        }

        return $dir;
    }
}
