<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Security\Guard;

class Logout
{
    public function main(): void
    {
        if (empty($_SESSION['user_code']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !Guard::csrfValid()) {
            Guard::redirect('?c=Login');
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => (string) $params['path'],
            'domain' => (string) $params['domain'],
            'secure' => true,
            'httponly' => (bool) $params['httponly'],
            'samesite' => (string) ($params['samesite'] ?? 'Strict'),
        ]);
        session_destroy();
        Guard::redirect('?');
    }
}
