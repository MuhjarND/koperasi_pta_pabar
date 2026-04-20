<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingService
{
    private static $cache = [];
    private static $tableAvailable = null;

    public function getBool(string $key, bool $default = true): bool
    {
        if (!$this->tableAvailable()) {
            return $default;
        }

        if (array_key_exists($key, self::$cache)) {
            return $this->toBool(self::$cache[$key], $default);
        }

        $value = DB::table('system_settings')->where('key', $key)->value('value');
        self::$cache[$key] = $value;

        return $this->toBool($value, $default);
    }

    public function setBool(string $key, bool $value, ?int $updatedBy = null): void
    {
        if (!$this->tableAvailable()) {
            return;
        }

        $stringValue = $value ? '1' : '0';
        $existing = DB::table('system_settings')->where('key', $key)->first();

        if ($existing) {
            DB::table('system_settings')
                ->where('id', $existing->id)
                ->update([
                    'value' => $stringValue,
                    'updated_by' => $updatedBy,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $stringValue,
                'updated_by' => $updatedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        self::$cache[$key] = $stringValue;
    }

    private function toBool($value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private function tableAvailable(): bool
    {
        if (self::$tableAvailable !== null) {
            return self::$tableAvailable;
        }

        try {
            self::$tableAvailable = Schema::hasTable('system_settings');
        } catch (\Throwable $e) {
            self::$tableAvailable = false;
        }

        return self::$tableAvailable;
    }
}
