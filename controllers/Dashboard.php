<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Security\Router;
use App\Security\View;

class Dashboard
{
    public function main(): void
    {
        $role = (string) ($_SESSION['role'] ?? '');
        View::render(Router::layout($role)['home'], ['session' => $role]);
    }
}
