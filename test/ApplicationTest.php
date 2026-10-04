<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\Login;
use App\Controllers\Users;
use App\Models\DataBase;
use App\Models\Role;
use App\Models\RoleRepository;
use App\Models\User;
use App\Models\UserRepository;
use App\Security\AppException;
use App\Security\FrontController;
use App\Security\Guard;
use App\Security\RedirectException;
use App\Security\View;
use PDO;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        putenv('LOGIN_ATTEMPT_DIR=/tmp/inventario-attempts');
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE ROLES (
                rol_code INTEGER PRIMARY KEY AUTOINCREMENT,
                rol_name TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE USERS (
                rol_code INTEGER NOT NULL,
                user_code INTEGER PRIMARY KEY AUTOINCREMENT,
                user_name TEXT NOT NULL,
                user_lastname TEXT NOT NULL,
                user_id TEXT NOT NULL,
                user_email TEXT NOT NULL UNIQUE,
                user_pass TEXT NOT NULL,
                user_state INTEGER NOT NULL
            )'
        );
        DataBase::setConnection($this->pdo);
        $roles = new RoleRepository();
        $roles->create('admin');
        $roles->create('seller');
        $roles->create('customer');
        $admin = new User();
        $admin->setRolCode(1);
        $admin->setUserName('Ana');
        $admin->setUserLastName('Admin');
        $admin->setUserId('123456');
        $admin->setUserEmail('admin@example.com');
        $admin->setUserState(1);
        (new UserRepository())->create($admin, 'Admin12345');
        $seller = new User();
        $seller->setRolCode(2);
        $seller->setUserName('Sara');
        $seller->setUserLastName('Ventas');
        $seller->setUserId('223344');
        $seller->setUserEmail('sara@example.com');
        $seller->setUserState(1);
        (new UserRepository())->create($seller, 'Seller123');
    }

    protected function tearDown(): void
    {
        DataBase::setConnection(null);
        \App\Security\Kernel::restore();
    }

    public function testRepositoriesPersistAndRejectBadLogin(): void
    {
        $users = new UserRepository();
        $admin = $users->login('admin@example.com', 'Admin12345');
        self::assertNotNull($admin);
        self::assertSame('admin', $admin->getRolName());
        self::assertSame('', $admin->getUserPass());
        self::assertNull($users->login('admin@example.com', 'clave-mala'));
        self::assertNull($users->login('nadie@example.com', 'Admin12345'));

        $inactive = new User();
        $inactive->setRolCode(3);
        $inactive->setUserName('Ines');
        $inactive->setUserLastName('Cero');
        $inactive->setUserId('999999');
        $inactive->setUserEmail('inactiva@example.com');
        $inactive->setUserState(0);
        self::assertTrue($users->create($inactive, 'Cliente123'));
        self::assertNull($users->login('inactiva@example.com', 'Cliente123'));
        self::assertTrue($users->emailTaken('admin@example.com'));
        self::assertFalse($users->emailTaken('libre@example.com'));

        $found = $users->find(1);
        self::assertNotNull($found);
        $found->setUserName('Ana Maria');
        self::assertTrue($users->update($found, null));
        self::assertSame('Ana Maria', $users->find(1)?->getUserName());
        self::assertTrue($users->update($found, 'Nueva1234'));
        self::assertNotNull($users->login('admin@example.com', 'Nueva1234'));
        self::assertSame(1, $users->countByRoleName('admin'));
        self::assertNull($users->find(999));

        $roles = new RoleRepository();
        self::assertCount(3, $roles->all());
        self::assertTrue($roles->create('bodega'));
        $bodega = $roles->findByName('bodega');
        self::assertInstanceOf(Role::class, $bodega);
        self::assertTrue($roles->update($bodega->getRolCode(), 'deposito'));
        self::assertSame('deposito', $roles->find($bodega->getRolCode())?->getRolName());
        self::assertSame(1, $roles->countUsers(1));
        self::assertTrue($roles->delete($bodega->getRolCode()));
        self::assertFalse($roles->delete(999));
    }

    public function testEntitiesExposeAssignedValues(): void
    {
        $user = new User();
        $user->setRolCode(2);
        $user->setRolName('seller');
        $user->setUserCode(8);
        $user->setUserName('Luis');
        $user->setUserLastName('Perez');
        $user->setUserId('55555');
        $user->setUserEmail('luis@example.com');
        $user->setUserPass('oculto');
        $user->setUserState(1);
        self::assertSame(2, $user->getRolCode());
        self::assertSame('seller', $user->getRolName());
        self::assertSame(8, $user->getUserCode());
        self::assertSame('Luis', $user->getUserName());
        self::assertSame('Perez', $user->getUserLastName());
        self::assertSame('55555', $user->getUserId());
        self::assertSame('luis@example.com', $user->getUserEmail());
        self::assertSame('oculto', $user->getUserPass());
        self::assertSame(1, $user->getUserState());
    }

    public function testDatabaseRejectsMissingPassword(): void
    {
        DataBase::setConnection(null);
        putenv('DB_HOST');
        putenv('DB_PORT');
        putenv('DB_NAME');
        putenv('DB_USER');
        putenv('DB_PASSWORD');
        $this->expectException(AppException::class);
        DataBase::connection();
    }

    public function testViewRejectsPathsOutsideTemplates(): void
    {
        $this->expectException(AppException::class);
        View::render('../models/DataBase.php');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPublicPagesAndBlockedRoutes(): void
    {
        $_GET = [];
        $home = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('Zay', $home);

        $_GET = ['c' => 'Login'];
        $login = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('csrf_token', $login);

        $_GET = ['c' => '../models/User', 'a' => 'main'];
        self::assertSame('?', FrontController::handle());

        $_GET = ['c' => 'Users', 'a' => 'userDelete'];
        self::assertSame('?c=Login', FrontController::handle());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoginRejectsInvalidCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['c' => 'Login'];
        $_POST = ['user_email' => 'admin@example.com', 'user_pass' => 'Admin12345', 'csrf_token' => 'malo'];
        $invalid = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('no es válida', $invalid);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoginRejectsWrongPassword(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['c' => 'Login'];
        $_POST = [
            'user_email' => 'admin@example.com',
            'user_pass' => 'clave-mala',
            'csrf_token' => Guard::csrfToken(),
        ];
        $wrong = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('Credenciales incorrectas', $wrong);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoginStartsSession(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['c' => 'Login'];
        $_POST = [
            'user_email' => 'admin@example.com',
            'user_pass' => 'Admin12345',
            'csrf_token' => Guard::csrfToken(),
        ];
        self::assertSame('?c=Dashboard', FrontController::handle());
        self::assertSame(1, $_SESSION['user_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticatedFlowsEnforceRoles(): void
    {
        $this->actAs(1, 'admin');
        $_GET = ['c' => 'Dashboard'];
        $dashboard = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('ADMIN', $dashboard);

        $_GET = ['c' => 'Users', 'a' => 'userRead'];
        $list = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('admin@example.com', $list);
        self::assertStringContainsString('csrf_token', $list);

        $_GET = ['c' => 'Users', 'a' => 'userCreate'];
        $form = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringContainsString('>admin<', $form);

        $_GET = ['c' => 'Users', 'a' => 'rolDelete'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => 'malo', 'idRol' => '1'];
        self::assertSame('?c=Dashboard', FrontController::handle());

        $this->actAs(1, 'admin');
        $_GET = ['c' => 'Logout'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => 'malo'];
        self::assertSame('?c=Login', FrontController::handle());

        $_POST = ['csrf_token' => Guard::csrfToken()];
        self::assertSame('?', FrontController::handle());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSellerCannotAssignAdministrator(): void
    {
        $this->actAs(2, 'seller');
        $_GET = ['c' => 'Users', 'a' => 'userCreate'];
        $sellerForm = $this->capture(static function (): void {
            FrontController::handle();
        });
        self::assertStringNotContainsString('>admin<', $sellerForm);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $this->userPayload(1, Guard::csrfToken());
        self::assertSame('?c=Users&a=userCreate', FrontController::handle());
    }

    public function testAdminModuleValidatesAndProtectsAccounts(): void
    {
        $_SESSION['user_code'] = 1;
        $_SESSION['role'] = 'admin';
        $module = new Users();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->capture(static function () use ($module): void {
            $module->rolCreate();
            $module->rolRead();
            $module->userCreate();
            $module->userRead();
        });
        $_GET['idUser'] = '2';
        $updateForm = $this->capture(static function () use ($module): void {
            $module->userUpdate();
        });
        self::assertStringContainsString('sara@example.com', $updateForm);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => Guard::csrfToken(), 'rol_name' => 'bodega'];
        self::assertSame('?c=Users&a=rolRead', $this->redirect(static function () use ($module): void {
            $module->rolCreate();
        }));
        $bodega = (new RoleRepository())->findByName('bodega');
        self::assertNotNull($bodega);

        $_GET['idRol'] = (string) $bodega->getRolCode();
        $this->capture(static function () use ($module): void {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $module->rolUpdate();
        });
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => Guard::csrfToken(),
            'rol_code' => (string) $bodega->getRolCode(),
            'rol_name' => 'deposito',
        ];
        self::assertSame('?c=Users&a=rolRead', $this->redirect(static function () use ($module): void {
            $module->rolUpdate();
        }));

        $_POST = ['csrf_token' => Guard::csrfToken(), 'idRol' => '1'];
        self::assertSame('?c=Users&a=rolRead', $this->redirect(static function () use ($module): void {
            $module->rolDelete();
        }));
        self::assertNotNull((new RoleRepository())->find(1));

        $_POST = ['csrf_token' => Guard::csrfToken(), 'idUser' => '1'];
        self::assertSame('?c=Users&a=userRead', $this->redirect(static function () use ($module): void {
            $module->userDelete();
        }));
        self::assertNotNull((new UserRepository())->find(1));

        $_POST = $this->userPayload(3, Guard::csrfToken());
        $_POST['user_email'] = 'nueva@example.com';
        self::assertSame('?c=Users&a=userRead', $this->redirect(static function () use ($module): void {
            $module->userCreate();
        }));

        $_POST = [
            'csrf_token' => Guard::csrfToken(),
            'user_code' => '2',
            'rol_code' => '2',
            'user_state' => '1',
            'user_name' => 'Sara',
            'user_lastname' => 'Lopez',
            'user_id' => '223344',
            'user_email' => 'sara@example.com',
            'user_pass' => '',
            'user_pass_conf' => '',
        ];
        self::assertSame('?c=Users&a=userRead', $this->redirect(static function () use ($module): void {
            $module->userUpdate();
        }));
        self::assertSame('Lopez', (new UserRepository())->find(2)?->getUserLastName());

        self::assertSame('?c=Dashboard', $this->redirect(static function () use ($module): void {
            $module->main();
        }));
    }

    public function testLoginCoversRemainingBranches(): void
    {
        DataBase::setConnection(null);
        putenv('DB_HOST');
        putenv('DB_PORT');
        putenv('DB_NAME');
        putenv('DB_USER');
        putenv('DB_PASSWORD');
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['c' => 'Login'];
        $_POST = [
            'user_email' => 'admin@example.com',
            'user_pass' => 'Admin12345',
            'csrf_token' => Guard::csrfToken(),
        ];
        $page = $this->capture(static function (): void {
            (new Login())->main();
        });
        self::assertStringContainsString('No fue posible iniciar sesión', $page);

        $_POST = ['user_email' => 'no-es-correo', 'user_pass' => '', 'csrf_token' => Guard::csrfToken()];
        (new Login())->main();

        $_SESSION['user_code'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::assertSame('?c=Dashboard', $this->redirect(static function (): void {
            (new Login())->main();
        }));
    }

    public function testRedirectRejectsExternalTargets(): void
    {
        try {
            Guard::redirect("?\nLocation: https://evil.test");
            self::fail('La redirección debía interrumpir la ejecución.');
        } catch (RedirectException $redirect) {
            self::assertSame('?', $redirect->target());
        }
    }

    public function testEnvironmentFileLoadsNewKeys(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($path, "TEST_ONLY_KEY=valor\n# comentario\n=\n");
        putenv('TEST_ONLY_KEY');
        Guard::loadEnv($path);
        self::assertSame('valor', getenv('TEST_ONLY_KEY'));
        unlink($path);
        Guard::flashSet('aviso');
        self::assertSame('aviso', Guard::flashGet());
        self::assertSame('', Guard::flashGet());
        session_write_close();
        Guard::startSession();
        Guard::sendHeaders();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testIndexEntryPointRendersLanding(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        ob_start();
        require dirname(__DIR__) . '/index.php';
        $html = (string) ob_get_clean();
        self::assertStringContainsString('Zay', $html);
    }

    private function actAs(int $userCode, string $role): void
    {
        $_SESSION['user_code'] = $userCode;
        $_SESSION['role'] = $role;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
    }

    private function userPayload(int $roleCode, string $token): array
    {
        return [
            'csrf_token' => $token,
            'rol_code' => (string) $roleCode,
            'user_state' => '1',
            'user_name' => 'Nora',
            'user_lastname' => 'Diaz',
            'user_id' => '1234567',
            'user_email' => 'nora@example.com',
            'user_pass' => 'Nora12345',
            'user_pass_conf' => 'Nora12345',
        ];
    }

    private function capture(callable $action): string
    {
        ob_start();
        $action();
        return (string) ob_get_clean();
    }

    private function redirect(callable $action): string
    {
        try {
            $action();
        } catch (RedirectException $redirect) {
            return $redirect->target();
        }
        self::fail('Se esperaba una redirección.');
    }
}
