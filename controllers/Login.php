<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserRepository;
use App\Security\Guard;
use App\Security\InputValidator;
use App\Security\LoginThrottle;
use App\Security\View;
use Throwable;

class Login
{
    private const LOGIN_VIEW = 'views/company/login.view.php';

    public function main(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->authenticate();
            return;
        }
        if (!empty($_SESSION['user_code'])) {
            Guard::redirect('?c=Dashboard');
        }
        View::render(self::LOGIN_VIEW, ['message' => '']);
    }

    private function authenticate(): void
    {
        $message = 'Credenciales incorrectas.';
        $email = trim((string) ($_POST['user_email'] ?? ''));
        $password = (string) ($_POST['user_pass'] ?? '');

        if (!Guard::csrfValid()) {
            $message = 'La solicitud no es válida. Recargue la página.';
        } elseif (LoginThrottle::blocked($email)) {
            $message = 'Demasiados intentos. Espere 15 minutos e intente de nuevo.';
        } elseif (InputValidator::email($email) && $password !== '') {
            try {
                $profile = (new UserRepository())->login($email, $password);
            } catch (Throwable $error) {
                error_log($error->getMessage());
                $message = 'No fue posible iniciar sesión en este momento.';
                View::render(self::LOGIN_VIEW, ['message' => $message]);
                return;
            }
            if ($profile !== null) {
                LoginThrottle::clear($email);
                session_regenerate_id(true);
                $_SESSION['user_code'] = $profile->getUserCode();
                $_SESSION['role'] = $profile->getRolName();
                Guard::redirect('?c=Dashboard');
            }
            LoginThrottle::fail($email);
        } else {
            LoginThrottle::fail($email);
        }

        View::render(self::LOGIN_VIEW, ['message' => $message]);
    }
}
