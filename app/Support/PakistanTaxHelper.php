<?php

namespace App\Support;

final class PakistanTaxHelper
{
    public static function normalizeCnic(string $value): string
    {
        return self::stripSeparators($value);
    }

    public static function normalizeNtn(string $value): string
    {
        return self::stripSeparators($value);
    }

    public static function isValidCnic(string $value): bool
    {
        $normalized = self::normalizeCnic($value);

        return strlen($normalized) === 13 && ctype_digit($normalized);
    }

    public static function isValidNtn(string $value): bool
    {
        $normalized = self::normalizeNtn($value);

        if (! ctype_digit($normalized)) {
            return false;
        }

        return strlen($normalized) === 13 || strlen($normalized) === 8;
    }

    private static function stripSeparators(string $value): string
    {
        return preg_replace('/[\s-]+/', '', trim($value)) ?? '';
    }
}
