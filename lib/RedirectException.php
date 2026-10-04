<?php

declare(strict_types=1);

namespace App\Security;

final class RedirectException extends \Exception
{
    public function __construct(private readonly string $target)
    {
        parent::__construct('Redirección');
    }

    public function target(): string
    {
        return $this->target;
    }
}
