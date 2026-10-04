<?php

declare(strict_types=1);

namespace Tests;

use App\Security\Guard;
use App\Security\InputValidator;
use App\Security\LoginThrottle;
use App\Security\RedirectException;
use App\Security\Router;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_POST = [];
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        putenv('LOGIN_ATTEMPT_DIR=/tmp/inventario-attempts');
        if (is_dir('/tmp/inventario-attempts')) {
            foreach (glob('/tmp/inventario-attempts/*.json') ?: [] as $file) {
                unlink($file);
            }
        }
    }

    public function testEscapePreventsHtmlInjection(): void
    {
        $escaped = Guard::e('<script>alert("x")</script>');
        self::assertStringNotContainsString('<script>', $escaped);
        self::assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $escaped);
    }

    public function testCsrfRejectsMissingAndAcceptsMatchingToken(): void
    {
        self::assertFalse(Guard::csrfValid());
        $token = Guard::csrfToken();
        $_POST['csrf_token'] = 'otro-token';
        self::assertFalse(Guard::csrfValid());
        $_POST['csrf_token'] = $token;
        self::assertTrue(Guard::csrfValid());
        self::assertStringContainsString($token, Guard::csrfField());
    }

    public function testPasswordUsesModernHash(): void
    {
        $hash = Guard::hashPassword('Admin12345');
        self::assertTrue(Guard::verifyPassword('Admin12345', $hash));
        self::assertFalse(Guard::verifyPassword('otra-clave', $hash));
        self::assertStringStartsWith('$2', $hash);
        self::assertFalse(InputValidator::password('12345'));
        self::assertTrue(InputValidator::password('Admin12345'));
    }

    public function testRouterRejectsPathTraversalAndUnknownActions(): void
    {
        self::assertNull(Router::resolve('../models/User', 'main'));
        self::assertNull(Router::resolve('Login', 'login'));
        self::assertNull(Router::resolve('Users', 'delete_user'));
        $route = Router::resolve('Users', 'userDelete');
        self::assertNotNull($route);
        self::assertFalse($route['public']);
        self::assertSame('userDelete', $route['action']);
    }

    public function testLayoutUsesFixedTemplates(): void
    {
        $admin = Router::layout('admin');
        $injected = Router::layout('../../etc/passwd');
        self::assertSame('views/roles/admin/admin.view.php', $admin['home']);
        self::assertSame('views/roles/customer/customer.view.php', $injected['home']);
        self::assertStringNotContainsString('..', implode(' ', $injected));
    }

    public function testReservedRolesCannotBeReused(): void
    {
        self::assertTrue(InputValidator::isProtectedRole('Admin'));
        self::assertTrue(InputValidator::isProtectedRole('seller'));
        self::assertFalse(InputValidator::isProtectedRole('bodega'));
        self::assertTrue(InputValidator::roleName('bodega'));
        self::assertFalse(InputValidator::roleName('../admin'));
        self::assertTrue(InputValidator::email('persona@example.com'));
        self::assertFalse(InputValidator::email('no-es-correo'));
        self::assertTrue(InputValidator::document('123456'));
        self::assertFalse(InputValidator::document('12'));
        self::assertTrue(InputValidator::personName('Ana María'));
        self::assertFalse(InputValidator::personName('Ana1'));
        self::assertTrue(InputValidator::state('0'));
        self::assertTrue(InputValidator::state(1));
        self::assertFalse(InputValidator::state('2'));
    }

    public function testRedirectStaysInsideTheApplication(): void
    {
        try {
            Guard::redirect('https://evil.test');
            self::fail('La redirección debía interrumpir la ejecución.');
        } catch (RedirectException $redirect) {
            self::assertSame('?', $redirect->target());
        }
    }

    public function testLoginThrottleBlocksAfterFiveFailures(): void
    {
        $email = 'persona@example.com';
        self::assertFalse(LoginThrottle::blocked($email));
        for ($attempt = 0; $attempt < 5; $attempt++) {
            LoginThrottle::fail($email);
        }
        self::assertTrue(LoginThrottle::blocked($email));
        LoginThrottle::clear($email);
        self::assertFalse(LoginThrottle::blocked($email));
    }
}
