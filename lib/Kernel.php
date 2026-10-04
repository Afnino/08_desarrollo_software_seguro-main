<?php

declare(strict_types=1);

namespace App\Security;

use Throwable;

final class Kernel
{
    private static bool $handlerReady = false;

    public static function boot(): void
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        if (!self::$handlerReady) {
            self::registerHandler();
            self::$handlerReady = true;
        }
        Guard::loadEnv(dirname(__DIR__) . '/.env');
        Guard::startSession();
        Guard::sendHeaders();
    }

    public static function restore(): void
    {
        if (self::$handlerReady) {
            restore_exception_handler();
            self::$handlerReady = false;
        }
    }

    private static function registerHandler(): void
    {
        set_exception_handler(static function (Throwable $error): void {
            error_log($error->getMessage());
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo 'Ocurrió un error interno.';
        });
    }
}
