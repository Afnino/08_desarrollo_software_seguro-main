<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\UserRepository;

final class FrontController
{
    public static function handle(): ?string
    {
        $level = ob_get_level();
        $target = null;
        try {
            Kernel::boot();
            ob_start();
            $controllerName = isset($_GET['c']) ? (string) $_GET['c'] : 'Landing';
            $actionName = isset($_GET['a']) ? (string) $_GET['a'] : 'main';
            $route = Router::resolve($controllerName, $actionName);
            if ($route === null) {
                Guard::redirect('?');
            }

            $controller = new ($route['class'])();
            if ($route['public']) {
                require_once dirname(__DIR__) . '/views/company/header.view.php';
                $controller->{$route['action']}();
                require_once dirname(__DIR__) . '/views/company/footer.view.php';
                self::release($level);
            } elseif ($controllerName === 'Logout') {
                $controller->main();
                self::release($level);
            } else {
                if (empty($_SESSION['user_code'])) {
                    Guard::redirect('?c=Login');
                }
                $profile = (new UserRepository())->find((int) $_SESSION['user_code']);
                if ($profile === null || $profile->getUserState() !== 1) {
                    $_SESSION = [];
                    session_destroy();
                    Guard::redirect('?c=Login');
                }
                $_SESSION['role'] = $profile->getRolName();
                $session = $profile->getRolName();
                header('Cache-Control: no-store');
                $layout = Router::layout($session);
                require_once dirname(__DIR__) . '/' . $layout['header'];
                $controller->{$route['action']}();
                require_once dirname(__DIR__) . '/' . $layout['footer'];
                self::release($level);
            }
        } catch (RedirectException $redirect) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            header('Location: ' . $redirect->target(), true, 302);
            $target = $redirect->target();
        }

        return $target;
    }

    private static function release(int $level): void
    {
        while (ob_get_level() > $level) {
            ob_end_flush();
        }
    }
}
