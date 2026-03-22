<?php

declare(strict_types=1);

namespace App\Support;

final class Cpf
{
    public static function sanitize(?string $value): string
    {
        return preg_replace('/\D+/', '', $value ?? '') ?? '';
    }

    public static function mask(?string $value): string
    {
        $cpf = self::sanitize($value);

        if (strlen($cpf) !== 11) {
            return '';
        }

        return sprintf('***.***.***-%s', substr($cpf, -2));
    }

    public static function isValid(?string $value): bool
    {
        $cpf = self::sanitize($value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += ((int) $cpf[$index]) * (($position + 1) - $index);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
