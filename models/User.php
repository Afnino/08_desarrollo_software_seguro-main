<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    private int $rolCode = 0;
    private string $rolName = '';
    private int $userCode = 0;
    private string $userName = '';
    private string $userLastname = '';
    private string $userId = '';
    private string $userEmail = '';
    private string $userPass = '';
    private int $userState = 0;

    public function setRolCode(int $rolCode): void
    {
        $this->rolCode = $rolCode;
    }

    public function getRolCode(): int
    {
        return $this->rolCode;
    }

    public function setRolName(string $rolName): void
    {
        $this->rolName = $rolName;
    }

    public function getRolName(): string
    {
        return $this->rolName;
    }

    public function setUserCode(int $userCode): void
    {
        $this->userCode = $userCode;
    }

    public function getUserCode(): int
    {
        return $this->userCode;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserLastName(string $userLastname): void
    {
        $this->userLastname = $userLastname;
    }

    public function getUserLastName(): string
    {
        return $this->userLastname;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserEmail(string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserPass(string $userPass): void
    {
        $this->userPass = $userPass;
    }

    public function getUserPass(): string
    {
        return $this->userPass;
    }

    public function setUserState(int $userState): void
    {
        $this->userState = $userState;
    }

    public function getUserState(): int
    {
        return $this->userState;
    }
}
