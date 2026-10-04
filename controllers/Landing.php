<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Security\View;

class Landing
{
    public function main(): void
    {
        View::render('views/company/index.view.php');
    }
}
