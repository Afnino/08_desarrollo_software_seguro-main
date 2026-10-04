<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class RoleRepository
{
    private const ROLE_NAME = ':rolName';
    private const ROLE_CODE = ':rolCode';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DataBase::connection();
    }

    public function create(string $roleName): bool
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO ROLES (rol_name) VALUES (:rolName)');
            $stmt->bindValue(self::ROLE_NAME, $roleName);
            $stmt->execute();

            return true;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function all(): array
    {
        $roles = [];
        $stmt = $this->pdo->query('SELECT rol_code, rol_name FROM ROLES ORDER BY rol_code');
        foreach ($stmt->fetchAll() as $row) {
            $roles[] = $this->map($row);
        }

        return $roles;
    }

    public function find(int $roleCode): ?Role
    {
        $stmt = $this->pdo->prepare(
            'SELECT rol_code, rol_name FROM ROLES WHERE rol_code = :rolCode LIMIT 1'
        );
        $stmt->bindValue(self::ROLE_CODE, $roleCode, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    public function findByName(string $roleName): ?Role
    {
        $stmt = $this->pdo->prepare(
            'SELECT rol_code, rol_name FROM ROLES WHERE rol_name = :rolName LIMIT 1'
        );
        $stmt->bindValue(self::ROLE_NAME, $roleName);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    public function update(int $roleCode, string $roleName): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE ROLES SET rol_name = :rolName WHERE rol_code = :rolCode'
            );
            $stmt->bindValue(self::ROLE_NAME, $roleName);
            $stmt->bindValue(self::ROLE_CODE, $roleCode, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function delete(int $roleCode): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM ROLES WHERE rol_code = :rolCode');
            $stmt->bindValue(self::ROLE_CODE, $roleCode, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function countUsers(int $roleCode): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM USERS WHERE rol_code = :rolCode');
        $stmt->bindValue(self::ROLE_CODE, $roleCode, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function map(array $row): Role
    {
        $role = new Role();
        $role->setRolCode((int) $row['rol_code']);
        $role->setRolName((string) $row['rol_name']);

        return $role;
    }
}
