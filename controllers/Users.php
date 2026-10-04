<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoleRepository;
use App\Models\User;
use App\Models\UserRepository;
use App\Security\Guard;
use App\Security\InputValidator;
use App\Security\View;

class Users
{
    private const DASHBOARD = '?c=Dashboard';
    private const ROLES = '?c=Users&a=rolRead';
    private const PEOPLE = '?c=Users&a=userRead';

    public function main(): void
    {
        Guard::redirect(self::DASHBOARD);
    }

    public function rolCreate(): void
    {
        if (!$this->isAdmin()) {
            Guard::redirect(self::DASHBOARD);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->storeRole();
            return;
        }
        View::render('views/modules/users/rol_create.view.php');
    }

    public function rolRead(): void
    {
        if (!$this->isAdmin()) {
            Guard::redirect(self::DASHBOARD);
        }
        View::render('views/modules/users/rol_read.view.php', [
            'roles' => (new RoleRepository())->all(),
        ]);
    }

    public function rolUpdate(): void
    {
        if (!$this->isAdmin()) {
            Guard::redirect(self::DASHBOARD);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->changeRole();
            return;
        }
        $roleId = $this->positiveInt('get', 'idRol');
        $rolId = $roleId === null ? null : (new RoleRepository())->find($roleId);
        if ($rolId === null) {
            Guard::flashSet('El rol no existe.');
            Guard::redirect(self::ROLES);
        }
        View::render('views/modules/users/rol_update.view.php', ['rolId' => $rolId]);
    }

    public function rolDelete(): void
    {
        if (!$this->isAdmin() || !$this->validPost()) {
            Guard::redirect(self::DASHBOARD);
        }
        $roleId = $this->positiveInt('post', 'idRol');
        $repository = new RoleRepository();
        $role = $roleId === null ? null : $repository->find($roleId);
        if ($role === null || InputValidator::isProtectedRole($role->getRolName())) {
            Guard::flashSet('Ese rol no se puede eliminar.');
        } elseif ($repository->countUsers($role->getRolCode()) > 0) {
            Guard::flashSet('No se puede eliminar un rol que todavía tiene usuarios.');
        } elseif (!$repository->delete($role->getRolCode())) {
            Guard::flashSet('No se pudo eliminar el rol.');
        }
        Guard::redirect(self::ROLES);
    }

    public function userCreate(): void
    {
        if (!$this->canManageUsers()) {
            Guard::redirect(self::DASHBOARD);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->storeUser();
            return;
        }
        View::render('views/modules/users/user_create.view.php', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function userRead(): void
    {
        if (!$this->canManageUsers()) {
            Guard::redirect(self::DASHBOARD);
        }
        View::render('views/modules/users/user_read.view.php', [
            'users' => (new UserRepository())->all(),
        ]);
    }

    public function userUpdate(): void
    {
        if (!$this->canManageUsers()) {
            Guard::redirect(self::DASHBOARD);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->changeUser();
            return;
        }
        $userId = $this->positiveInt('get', 'idUser');
        $user = $userId === null ? null : (new UserRepository())->find($userId);
        if ($user === null || !$this->canManage($user)) {
            Guard::flashSet('No puede consultar ese usuario.');
            Guard::redirect(self::PEOPLE);
        }
        View::render('views/modules/users/user_update.view.php', [
            'roles' => $this->assignableRoles(),
            'user' => $user,
        ]);
    }

    public function userDelete(): void
    {
        if (!$this->isAdmin() || !$this->validPost()) {
            Guard::redirect(self::DASHBOARD);
        }
        $userId = $this->positiveInt('post', 'idUser');
        $repository = new UserRepository();
        $user = $userId === null ? null : $repository->find($userId);
        if ($user === null) {
            Guard::flashSet('El usuario no existe.');
        } elseif ((int) $_SESSION['user_code'] === $user->getUserCode()) {
            Guard::flashSet('No puede eliminar su propio usuario.');
        } elseif ($user->getRolName() === 'admin' && $repository->countByRoleName('admin') <= 1) {
            Guard::flashSet('Debe conservar al menos un administrador.');
        } elseif (!$repository->delete($user->getUserCode())) {
            Guard::flashSet('No se pudo eliminar el usuario.');
        }
        Guard::redirect(self::PEOPLE);
    }

    private function storeRole(): void
    {
        if (!$this->validPost()) {
            Guard::redirect(self::ROLES);
        }
        $name = mb_strtolower(trim((string) ($_POST['rol_name'] ?? '')), 'UTF-8');
        $repository = new RoleRepository();
        if (!InputValidator::roleName($name) || InputValidator::isProtectedRole($name) || $repository->findByName($name) !== null) {
            Guard::flashSet('El nombre del rol no es válido o ya existe.');
            Guard::redirect('?c=Users&a=rolCreate');
        }
        if (!$repository->create($name)) {
            Guard::flashSet('No se pudo registrar el rol.');
        }
        Guard::redirect(self::ROLES);
    }

    private function changeRole(): void
    {
        if (!$this->validPost()) {
            Guard::redirect(self::ROLES);
        }
        $roleId = $this->positiveInt('post', 'rol_code');
        $name = mb_strtolower(trim((string) ($_POST['rol_name'] ?? '')), 'UTF-8');
        $repository = new RoleRepository();
        $current = $roleId === null ? null : $repository->find($roleId);
        $duplicate = $repository->findByName($name);
        $nameTaken = $duplicate !== null && $duplicate->getRolCode() !== $roleId;
        if (
            $current === null
            || !InputValidator::roleName($name)
            || InputValidator::isProtectedRole($current->getRolName())
            || InputValidator::isProtectedRole($name)
            || $nameTaken
        ) {
            Guard::flashSet('No se puede actualizar ese rol.');
            Guard::redirect(self::ROLES);
        }
        if (!$repository->update($current->getRolCode(), $name)) {
            Guard::flashSet('No se pudo actualizar el rol.');
        }
        Guard::redirect(self::ROLES);
    }

    private function storeUser(): void
    {
        if (!$this->validPost()) {
            Guard::redirect(self::PEOPLE);
        }
        $user = $this->userFromPost(null);
        $password = (string) ($_POST['user_pass'] ?? '');
        $confirm = (string) ($_POST['user_pass_conf'] ?? '');
        $repository = new UserRepository();
        if ($user === null || !InputValidator::password($password) || !hash_equals($password, $confirm)) {
            Guard::flashSet('Revise los datos. La contraseña debe tener al menos 8 caracteres, una letra y un número.');
            Guard::redirect('?c=Users&a=userCreate');
        }
        if ($repository->emailTaken($user->getUserEmail())) {
            Guard::flashSet('Ese correo ya está registrado.');
            Guard::redirect('?c=Users&a=userCreate');
        }
        if (!$repository->create($user, $password)) {
            Guard::flashSet('No se pudo registrar el usuario.');
        }
        Guard::redirect(self::PEOPLE);
    }

    private function changeUser(): void
    {
        if (!$this->validPost()) {
            Guard::redirect(self::PEOPLE);
        }
        $userId = $this->positiveInt('post', 'user_code');
        $repository = new UserRepository();
        $current = $userId === null ? null : $repository->find($userId);
        $user = $current === null ? null : $this->userFromPost($current);
        $password = (string) ($_POST['user_pass'] ?? '');
        $confirm = (string) ($_POST['user_pass_conf'] ?? '');
        $changesPassword = $password !== '' || $confirm !== '';
        if ($current === null || $user === null || !$this->canManage($current)) {
            Guard::flashSet('No se pudo actualizar. Revise los datos, el rol y sus permisos.');
            Guard::redirect(self::PEOPLE);
        }
        if ($changesPassword && (!InputValidator::password($password) || !hash_equals($password, $confirm))) {
            Guard::flashSet('La contraseña nueva no cumple la política o no coincide.');
            Guard::redirect('?c=Users&a=userUpdate&idUser=' . $current->getUserCode());
        }
        if ($repository->emailTaken($user->getUserEmail(), $current->getUserCode())) {
            Guard::flashSet('Ese correo ya está registrado.');
            Guard::redirect('?c=Users&a=userUpdate&idUser=' . $current->getUserCode());
        }
        $user->setUserCode($current->getUserCode());
        if (!$repository->update($user, $changesPassword ? $password : null)) {
            Guard::flashSet('No se pudo actualizar el usuario.');
        }
        Guard::redirect(self::PEOPLE);
    }

    private function userFromPost(?User $current): ?User
    {
        $roleId = $this->positiveInt('post', 'rol_code');
        $name = trim((string) ($_POST['user_name'] ?? ''));
        $lastname = trim((string) ($_POST['user_lastname'] ?? ''));
        $document = trim((string) ($_POST['user_id'] ?? ''));
        $email = trim((string) ($_POST['user_email'] ?? ''));
        $state = (string) ($_POST['user_state'] ?? '');
        $role = $roleId === null ? null : (new RoleRepository())->find($roleId);
        $roleName = $role === null ? '' : $role->getRolName();
        $roleAllowed = $this->isAdmin() || in_array($roleName, ['seller', 'customer'], true);
        if (
            $role === null
            || !$roleAllowed
            || !InputValidator::personName($name)
            || !InputValidator::personName($lastname)
            || !InputValidator::document($document)
            || !InputValidator::email($email)
            || !InputValidator::state($state)
        ) {
            return null;
        }
        if ($current !== null && $current->getRolName() === 'admin' && $role->getRolName() !== 'admin' && (new UserRepository())->countByRoleName('admin') <= 1) {
            return null;
        }
        $user = new User();
        $user->setRolCode($role->getRolCode());
        $user->setRolName($role->getRolName());
        $user->setUserName($name);
        $user->setUserLastName($lastname);
        $user->setUserId($document);
        $user->setUserEmail($email);
        $user->setUserState((int) $state);

        return $user;
    }

    private function assignableRoles(): array
    {
        $roles = (new RoleRepository())->all();
        if ($this->isAdmin()) {
            return $roles;
        }

        return array_values(array_filter(
            $roles,
            fn ($role): bool => in_array($role->getRolName(), ['seller', 'customer'], true)
        ));
    }

    private function canManage(User $user): bool
    {
        return $this->isAdmin() || $user->getRolName() !== 'admin';
    }

    private function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    private function canManageUsers(): bool
    {
        return in_array($_SESSION['role'] ?? '', ['admin', 'seller'], true);
    }

    private function validPost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && Guard::csrfValid()) {
            return true;
        }
        Guard::flashSet('La solicitud no es válida. Recargue la página.');

        return false;
    }

    private function positiveInt(string $source, string $key): ?int
    {
        $bag = $source === 'post' ? $_POST : $_GET;
        $id = filter_var($bag[$key] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return null;
        }

        return $id;
    }
}
