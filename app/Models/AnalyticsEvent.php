<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOrganization;

class AnalyticsEvent extends Model
{
    use BelongsToOrganization;
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

