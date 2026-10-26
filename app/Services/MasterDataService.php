<?php

namespace App\Services;

class MasterDataService
{
    private static ?array $districts = null;

    private static ?array $subdistricts = null;

    private static ?array $ulbs = null;

    private static ?array $ulbWards = null;

    private static ?array $blocks = null;

    private static ?array $gps = null;

    private static ?array $masterData = null;

    /**
     * Parse JS files that assign window.masterDataV2.<prop> = [ ... ];
     *
     * @return array<int, array<string, mixed>>
     */
    public static function parseJsFile(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        // Find the array part
        $start = strpos($content, '[');
        $end = strrpos($content, ']');

        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $arrayStr = substr($content, $start, $end - $start + 1);

        // Normalize JS object syntax to valid JSON: quote unquoted object keys
        $json = preg_replace('/([{,]\s*)([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $arrayStr);

        // Remove trailing commas before } or ]
        $json = preg_replace('/,\s*([}\]])/', '$1', $json);

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get all districts.
     *
     * @return array<int, array{id: int, text: string}>
     */
    public static function getDistricts(): array
    {
        if (self::$districts === null) {
            self::$districts = self::parseJsFile(public_path('js/master-data/districts.js'));
        }

        return self::$districts;
    }

    /**
     * Get subdistricts optionally filtered by district_code.
     *
     * @return array<int, array{id: int, district_code: int, text: string}>
     */
    public static function getSubdistricts(?int $districtCode = null): array
    {
        if (self::$subdistricts === null) {
            self::$subdistricts = self::parseJsFile(public_path('js/master-data/subdistricts.js'));
        }

        if ($districtCode === null) {
            return self::$subdistricts;
        }

        return array_values(array_filter(self::$subdistricts, function ($item) use ($districtCode) {
            return (int) ($item['district_code'] ?? 0) === $districtCode;
        }));
    }

    /**
     * Get ULBs (Municipalities) filtered by district and optionally subdistrict.
     *
     * @return array<int, array{id: int, district_code: int, sub_district_code: int, text: string}>
     */
    public static function getUlbs(?int $districtCode = null, ?int $subDistrictCode = null): array
    {
        if (self::$ulbs === null) {
            self::$ulbs = self::parseJsFile(public_path('js/master-data/ulbs.js'));
        }

        return array_values(array_filter(self::$ulbs, function ($item) use ($districtCode, $subDistrictCode) {
            if ($districtCode !== null && (int) ($item['district_code'] ?? 0) !== $districtCode) {
                return false;
            }
            if ($subDistrictCode !== null && (int) ($item['sub_district_code'] ?? 0) !== $subDistrictCode) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Get Wards filtered by urban_body_code (Municipality id).
     *
     * @return array<int, array{id: int, urban_body_code: int, text: string}>
     */
    public static function getUlbWards(?int $urbanBodyCode = null): array
    {
        if (self::$ulbWards === null) {
            self::$ulbWards = self::parseJsFile(public_path('js/master-data/ulb_wards.js'));
        }

        if ($urbanBodyCode === null) {
            return self::$ulbWards;
        }

        return array_values(array_filter(self::$ulbWards, function ($item) use ($urbanBodyCode) {
            return (int) ($item['urban_body_code'] ?? 0) === $urbanBodyCode;
        }));
    }

    /**
     * Get Blocks filtered by district_code.
     *
     * @return array<int, array{id: int, district_code: int, text: string}>
     */
    public static function getBlocks(?int $districtCode = null): array
    {
        if (self::$blocks === null) {
            self::$blocks = self::parseJsFile(public_path('js/master-data/blocks.js'));
        }

        if ($districtCode === null) {
            return self::$blocks;
        }

        return array_values(array_filter(self::$blocks, function ($item) use ($districtCode) {
            return (int) ($item['district_code'] ?? 0) === $districtCode;
        }));
    }

    /**
     * Get Gram Panchayats filtered by district_code and block_code.
     *
     * @return array<int, array{id: int, district_code: int, block_code: int, text: string}>
     */
    public static function getGps(?int $districtCode = null, ?int $blockCode = null): array
    {
        if (self::$gps === null) {
            self::$gps = self::parseJsFile(public_path('js/master-data/gps.js'));
        }

        return array_values(array_filter(self::$gps, function ($item) use ($districtCode, $blockCode) {
            if ($districtCode !== null && (int) ($item['district_code'] ?? 0) !== $districtCode) {
                return false;
            }
            if ($blockCode !== null && (int) ($item['block_code'] ?? 0) !== $blockCode) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Get general master data (relations, genders, religions, categories, etc.).
     *
     * @return array<string, mixed>
     */
    public static function getMasterData(): array
    {
        if (self::$masterData === null) {
            $path = public_path('js/masterData.json');
            if (file_exists($path)) {
                $content = file_get_contents($path);
                self::$masterData = json_decode($content ?: '{}', true) ?: [];
            } else {
                self::$masterData = [];
            }
        }

        return self::$masterData;
    }
}
