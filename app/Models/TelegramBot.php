<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBot extends Model
{
    protected $fillable = [
        'organization_id','bot_id','name','token','last_update_id','is_enabled'
    ];
    protected $casts = [
        'is_enabled' => 'boolean',
        'last_update_id' => 'integer',
    ];
}
