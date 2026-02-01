<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
    ];

    /**
     * Get setting value with proper type casting
     */
    public function getValue()
    {
        return match ($this->setting_type) {
            'number' => (int) $this->setting_value,
            'boolean' => (bool) $this->setting_value,
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }

    /**
     * Set setting value
     */
    public function setValue($value): void
    {
        $this->setting_value = match ($this->setting_type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Get setting by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->getValue() : $default;
    }

    /**
     * Set setting by key
     */
    public static function set(string $key, $value, string $type = 'text'): void
    {
        $setting = static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_type' => $type]
        );
        $setting->setValue($value);
        $setting->save();
    }
}
