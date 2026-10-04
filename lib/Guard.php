<?php

declare(strict_types=1);

namespace App\Security;

final class Guard
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        $current = $_SESSION['csrf_token'] ?? '';
        if (!is_string($current) || $current === '') {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function csrfValid(): bool
    {
        $sent = (string) ($_POST['csrf_token'] ?? '');
        $stored = (string) ($_SESSION['csrf_token'] ?? '');

        return $stored !== '' && hash_equals($stored, $sent);
    }

    public static function redirect(string $url): void
    {
        if (!str_starts_with($url, '?') || str_contains($url, "\n") || str_contains($url, "\r")) {
            $url = '?';
        }
        throw new RedirectException($url);
    }

    public static function flashSet(string $message): void
    {
        $_SESSION['flash'] = $message;
    }

    public static function flashGet(): string
    {
        $message = (string) ($_SESSION['flash'] ?? '');
        unset($_SESSION['flash']);

        return $message;
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $plain, string $stored): bool
    {
        return $stored !== '' && password_verify($plain, $stored);
    }

    public static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if ($key === '' || getenv($key) !== false) {
                continue;
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    public static function sendHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "script-src 'self'; "
            . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
        );
    }
}
