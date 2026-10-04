<?php

declare(strict_types=1);

namespace App\Security;

use App\Controllers\Dashboard;
use App\Controllers\Landing;
use App\Controllers\Login;
use App\Controllers\Logout;
use App\Controllers\Users;

final class Router
{
    public static function resolve(string $controller, string $action): ?array
    {
        $routes = self::routes();
        if (!isset($routes[$controller]) || !in_array($action, $routes[$controller]['actions'], true)) {
            return null;
        }

        return [
            'class' => $routes[$controller]['class'],
            'action' => $action,
            'public' => $routes[$controller]['public'],
        ];
    }

    public static function layout(string $role): array
    {
        return match ($role) {
            'admin' => [
                'header' => 'views/roles/admin/header.view.php',
                'footer' => 'views/roles/admin/footer.view.php',
                'home' => 'views/roles/admin/admin.view.php',
            ],
            'seller' => [
                'header' => 'views/roles/seller/header.view.php',
                'footer' => 'views/roles/seller/footer.view.php',
                'home' => 'views/roles/seller/seller.view.php',
            ],
            default => [
                'header' => 'views/roles/customer/header.view.php',
                'footer' => 'views/roles/customer/footer.view.php',
                'home' => 'views/roles/customer/customer.view.php',
            ],
        };
    }

    private static function routes(): array
    {
        return [
            'Landing' => [
                'class' => Landing::class,
                'actions' => ['main'],
                'public' => true,
            ],
            'Login' => [
                'class' => Login::class,
                'actions' => ['main'],
                'public' => true,
            ],
            'Logout' => [
                'class' => Logout::class,
                'actions' => ['main'],
                'public' => false,
            ],
            'Dashboard' => [
                'class' => Dashboard::class,
                'actions' => ['main'],
                'public' => false,
            ],
            'Users' => [
                'class' => Users::class,
                'actions' => [
                    'main',
                    'rolCreate',
                    'rolRead',
                    'rolUpdate',
                    'rolDelete',
                    'userCreate',
                    'userRead',
                    'userUpdate',
                    'userDelete',
                ],
                'public' => false,
            ],
        ];
    }
}
