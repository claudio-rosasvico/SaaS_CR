<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bot extends Model
{
    protected $table = 'bots';

    protected $fillable = [
        'organization_id',
        'name',
        'channel',
        'is_default',
        'config',
        'embed_theme',
        'public_key',
    ];

    protected $casts = [
        'is_default'  => 'boolean',
        'config'      => 'array',
        'embed_theme' => 'array',
    ];

    protected static function booted(): void
    {
        // Al crear: si es web y no tiene key, la generamos
        static::creating(function (Bot $bot) {
            if ($bot->channel === 'web' && empty($bot->public_key)) {
                $bot->public_key = Str::random(40);
            }
        });

        // Al actualizar: si pasa a web o sigue siendo web y no tiene key, la generamos
        static::updating(function (Bot $bot) {
            if ($bot->channel === 'web' && empty($bot->public_key)) {
                $bot->public_key = Str::random(40);
            }
        });
    }
}
