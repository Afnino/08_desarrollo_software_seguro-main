<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\Guard;
use PDO;
use Throwable;

class UserRepository
{
    private const USER_CODE = ':userCode';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DataBase::connection();
    }

    public function login(string $email, string $password): ?User
    {
        $user = null;
        try {
            $stmt = $this->pdo->prepare(
                'SELECT r.rol_code, r.rol_name, u.user_code, u.user_name, u.user_lastname,
                        u.user_id, u.user_email, u.user_pass, u.user_state
                 FROM ROLES AS r
                 INNER JOIN USERS AS u ON r.rol_code = u.rol_code
                 WHERE u.user_email = :email
                 LIMIT 1'
            );
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $row = $stmt->fetch();
            if (is_array($row) && (int) $row['user_state'] === 1 && Guard::verifyPassword($password, (string) $row['user_pass'])) {
                $user = $this->map($row);
            }
        } catch (Throwable $error) {
            error_log($error->getMessage());
        }

        return $user;
    }

    public function create(User $user, string $password): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO USERS (
                    rol_code, user_name, user_lastname, user_id, user_email, user_pass, user_state
                 ) VALUES (
                    :rolCode, :userName, :userLastName, :userId, :userEmail, :userPass, :userState
                 )'
            );
            $stmt->bindValue(':rolCode', $user->getRolCode(), PDO::PARAM_INT);
            $stmt->bindValue(':userName', $user->getUserName());
            $stmt->bindValue(':userLastName', $user->getUserLastName());
            $stmt->bindValue(':userId', $user->getUserId());
            $stmt->bindValue(':userEmail', $user->getUserEmail());
            $stmt->bindValue(':userPass', Guard::hashPassword($password));
            $stmt->bindValue(':userState', $user->getUserState(), PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function all(): array
    {
        $users = [];
        $stmt = $this->pdo->query(
            'SELECT r.rol_code, r.rol_name, u.user_code, u.user_name, u.user_lastname,
                    u.user_id, u.user_email, u.user_state
             FROM ROLES AS r
             INNER JOIN USERS AS u ON r.rol_code = u.rol_code
             ORDER BY u.user_code'
        );
        foreach ($stmt->fetchAll() as $row) {
            $users[] = $this->map($row);
        }

        return $users;
    }

    public function find(int $userCode): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.rol_code, r.rol_name, u.user_code, u.user_name, u.user_lastname,
                    u.user_id, u.user_email, u.user_state
             FROM ROLES AS r
             INNER JOIN USERS AS u ON r.rol_code = u.rol_code
             WHERE u.user_code = :userCode
             LIMIT 1'
        );
        $stmt->bindValue(self::USER_CODE, $userCode, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    public function update(User $user, ?string $password): bool
    {
        try {
            if ($password === null) {
                $sql = 'UPDATE USERS SET
                            rol_code = :rolCode,
                            user_name = :userName,
                            user_lastname = :userLastName,
                            user_id = :userId,
                            user_email = :userEmail,
                            user_state = :userState
                        WHERE user_code = :userCode';
            } else {
                $sql = 'UPDATE USERS SET
                            rol_code = :rolCode,
                            user_name = :userName,
                            user_lastname = :userLastName,
                            user_id = :userId,
                            user_email = :userEmail,
                            user_pass = :userPass,
                            user_state = :userState
                        WHERE user_code = :userCode';
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':rolCode', $user->getRolCode(), PDO::PARAM_INT);
            $stmt->bindValue(':userName', $user->getUserName());
            $stmt->bindValue(':userLastName', $user->getUserLastName());
            $stmt->bindValue(':userId', $user->getUserId());
            $stmt->bindValue(':userEmail', $user->getUserEmail());
            $stmt->bindValue(':userState', $user->getUserState(), PDO::PARAM_INT);
            $stmt->bindValue(self::USER_CODE, $user->getUserCode(), PDO::PARAM_INT);
            if ($password !== null) {
                $stmt->bindValue(':userPass', Guard::hashPassword($password));
            }
            $stmt->execute();

            return true;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function delete(int $userCode): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM USERS WHERE user_code = :userCode');
            $stmt->bindValue(self::USER_CODE, $userCode, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Throwable $error) {
            error_log($error->getMessage());

            return false;
        }
    }

    public function emailTaken(string $email, ?int $exceptUserCode = null): bool
    {
        $sql = 'SELECT user_code FROM USERS WHERE user_email = :email';
        if ($exceptUserCode !== null) {
            $sql .= ' AND user_code <> :userCode';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        if ($exceptUserCode !== null) {
            $stmt->bindValue(self::USER_CODE, $exceptUserCode, PDO::PARAM_INT);
        }
        $stmt->execute();

        return is_array($stmt->fetch());
    }

    public function countByRoleName(string $roleName): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM USERS AS u
             INNER JOIN ROLES AS r ON r.rol_code = u.rol_code
             WHERE r.rol_name = :roleName'
        );
        $stmt->bindValue(':roleName', $roleName);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function map(array $row): User
    {
        $user = new User();
        $user->setRolCode((int) $row['rol_code']);
        $user->setRolName((string) $row['rol_name']);
        $user->setUserCode((int) $row['user_code']);
        $user->setUserName((string) $row['user_name']);
        $user->setUserLastName((string) $row['user_lastname']);
        $user->setUserId((string) $row['user_id']);
        $user->setUserEmail((string) $row['user_email']);
        $user->setUserState((int) $row['user_state']);

        return $user;
    }
}
