<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'description'];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.$key", 3600, function () use ($key, $default) {
            return self::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, $value, string $group = 'general'): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("setting.$key");
    }

    protected static function booted(): void
    {
        static::saved(fn ($s) => Cache::forget("setting.{$s->key}"));
        static::deleted(fn ($s) => Cache::forget("setting.{$s->key}"));
    }
}
