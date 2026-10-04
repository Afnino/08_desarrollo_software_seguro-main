<?php

declare(strict_types=1);

namespace App\Security;

final class View
{
    public static function render(string $relativePath, array $data = []): void
    {
        $root = dirname(__DIR__);
        $full = $root . '/' . ltrim($relativePath, '/');
        $real = realpath($full);
        $views = realpath($root . '/views');
        if ($real === false || $views === false || !str_starts_with($real, $views . DIRECTORY_SEPARATOR)) {
            throw new AppException('Vista no permitida.');
        }
        extract($data, EXTR_SKIP);
        require_once $real;
    }
}
