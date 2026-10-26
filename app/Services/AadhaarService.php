<?php

namespace App\Services;

class AadhaarService
{
    private static array $d = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    private static array $p = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];

    private static array $inv = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    /**
     * Validate an Indian Aadhaar number using Verhoeff algorithm.
     */
    public static function validate(string $num): bool
    {
        $num = preg_replace('/\D/', '', $num);
        if (strlen($num) !== 12) {
            return false;
        }

        $c = 0;
        $arr = array_reverse(array_map('intval', str_split($num)));
        for ($i = 0; $i < count($arr); $i++) {
            $c = self::$d[$c][self::$p[$i % 8][$arr[$i]]];
        }

        return $c === 0;
    }

    /**
     * Generate a valid 12-digit Indian Aadhaar number passing Verhoeff checksum.
     */
    public static function generate(): string
    {
        $digits = [random_int(2, 9)];
        for ($i = 0; $i < 10; $i++) {
            $digits[] = random_int(0, 9);
        }

        $c = 0;
        $rev = array_reverse($digits);
        for ($i = 0; $i < count($rev); $i++) {
            $c = self::$d[$c][self::$p[($i + 1) % 8][$rev[$i]]];
        }

        $digits[] = self::$inv[$c];

        return implode('', $digits);
    }
}
