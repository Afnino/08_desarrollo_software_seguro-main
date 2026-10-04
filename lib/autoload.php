<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Models\\' => '/models/',
        'App\\Controllers\\' => '/controllers/',
        'App\\Security\\' => '/lib/',
    ];
    $root = dirname(__DIR__);
    foreach ($prefixes as $prefix => $dir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $path = $root . $dir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
        return;
    }
});
