<?php

declare(strict_types=1);

namespace App\Security;

final class InputValidator
{
    public static function personName(string $value): bool
    {
        return preg_match('/^[\p{L} ]{1,35}$/u', $value) === 1;
    }

    public static function roleName(string $value): bool
    {
        return preg_match('/^[\p{L} ]{2,40}$/u', $value) === 1;
    }

    public static function isProtectedRole(string $value): bool
    {
        return in_array(mb_strtolower(trim($value), 'UTF-8'), ['admin', 'seller', 'customer'], true);
    }

    public static function email(string $value): bool
    {
        return strlen($value) <= 100 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function document(string $value): bool
    {
        return preg_match('/^[0-9()+]{5,20}$/', $value) === 1;
    }

    public static function password(string $value): bool
    {
        $length = strlen($value);

        return $length >= 8
            && $length <= 72
            && preg_match('/\p{L}/u', $value) === 1
            && preg_match('/\d/', $value) === 1;
    }

    public static function state(mixed $value): bool
    {
        return $value === '0' || $value === '1' || $value === 0 || $value === 1;
    }
}
