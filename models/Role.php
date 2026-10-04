<?php

declare(strict_types=1);

namespace App\Models;

class Role
{
    private int $rolCode = 0;
    private string $rolName = '';

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
}
