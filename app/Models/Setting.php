<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'label', 'description'];

    public static function getAllGrouped(): array
    {
        return self::all()
            ->groupBy('group')
            ->map(fn($items) => $items->mapWithKeys(fn($s) => [$s->key => self::castValue($s)]))
            ->toArray();
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => self::castValue($s)])
            ->toArray();
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = self::where('group', $group)->where('key', $key)->first();
        return $setting ? self::castValue($setting) : $default;
    }

    public static function set(string $group, string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    public static function updateGroup(string $group, array $data): void
    {
        // updateOrCreate (not a plain update()) so a key that has no row yet
        // — e.g. a new counter type added after the settings seeder last ran
        // — actually gets persisted instead of silently no-oping.
        foreach ($data as $key => $value) {
            self::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }
    }

    private static function castValue(self $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }
}
