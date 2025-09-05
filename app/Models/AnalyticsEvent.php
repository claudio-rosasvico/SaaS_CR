<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'organization_id',
        'conversation_id',
        'provider',
        'model',
        'duration_ms',
        'tokens_in',
        'tokens_out',
    ];
}
