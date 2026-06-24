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

    public static function normalizeFbrSellerRegistration(string $value): string
    {
        $normalized = strtoupper(str_replace(' ', '', trim($value)));

        if (preg_match('/^([A-Z]\d{6})-\d$/', $normalized, $matches)) {
            return $matches[1];
        }

        return str_replace('-', '', $normalized);
    }

    public static function normalizeSellerTaxNumber(string $value): string
    {
        $normalized = strtoupper(str_replace(' ', '', trim($value)));

        if (preg_match('/^[A-Z]\d{6}-\d$/', $normalized)) {
            return $normalized;
        }

        return self::normalizeNtn($normalized);
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

    public static function isValidFbrSellerRegistration(string $value): bool
    {
        $normalized = self::normalizeFbrSellerRegistration($value);

        return self::isValidCnic($normalized)
            || preg_match('/^[A-Z]\d{6}$/', $normalized) === 1;
    }

    public static function isValidSellerTaxNumber(string $value): bool
    {
        $normalized = self::normalizeSellerTaxNumber($value);

        return (ctype_digit($normalized) && strlen($normalized) === 8)
            || preg_match('/^[A-Z]\d{6}-\d$/', $normalized) === 1;
    }

    private static function stripSeparators(string $value): string
    {
        return preg_replace('/[\s-]+/', '', trim($value)) ?? '';
    }
}
