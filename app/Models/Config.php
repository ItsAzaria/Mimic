<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    const MAX_CODEBLOCK_SIZE = 'MAX_CODEBLOCK_SIZE';
    const LOGGING_CHANNEL_ID = 'LOGGING_CHANNEL_ID';
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $config = self::where('key', $key)->first();

        return $config ? $config->value : $default;
    }

    public static function set($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
