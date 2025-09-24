<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'nombre','email','empresa','servicio','mensaje','status',
        'responded_at','response_subject','response_body','responded_by',
        'ip','user_agent','referer','meta','website'
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'meta' => 'array',
    ];
}
