<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    public static function defaults(): array
    {
        $defaults = [];
        foreach (config('store.groups') as [$label, $fields]) {
            foreach ($fields as $key => $field) {
                $defaults[$key] = $field[2];
            }
        }

        return $defaults;
    }

    public static function allValues(): array
    {
        return array_replace(static::defaults(), static::query()->pluck('value', 'key')->map(fn ($value) => $value ?? '')->all());
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.$key", 3600, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function put(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("setting.$key");
    }
}
