<?php

namespace App\Helpers;

class Security
{
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function clean(string $value): string
    {
        return trim(strip_tags($value));
    }

    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePhone(string $phone): bool
    {
        return (bool) preg_match('/^\+?[0-9]{8,15}$/', preg_replace('/[\s\-.]/', '', $phone));
    }

    public static function strongPassword(string $password): bool
    {
        // Au moins 8 caractères (comptage Unicode)
        return mb_strlen($password) >= 8;
    }
}
