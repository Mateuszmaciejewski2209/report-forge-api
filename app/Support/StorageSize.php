<?php

namespace App\Support;

class StorageSize
{
    public static function parseToBytes(string $size): int
    {
        if (preg_match('/([\d.]+)\s*(KB|MB|GB)/i', $size, $m)) {
            $value = (float) $m[1];
            $unit = strtoupper($m[2]);

            return (int) round(match ($unit) {
                'GB' => $value * 1024 * 1024 * 1024,
                'MB' => $value * 1024 * 1024,
                default => $value * 1024,
            });
        }

        return 0;
    }

    public static function bytesToMb(int $bytes): float
    {
        return round($bytes / (1024 * 1024), 2);
    }

    public static function formatMb(float $mb): string
    {
        if ($mb >= 1024) {
            return round($mb / 1024, 1).' GB';
        }

        return round($mb, 1).' MB';
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        return max(1, (int) ceil($bytes / 1024)).' KB';
    }
}
