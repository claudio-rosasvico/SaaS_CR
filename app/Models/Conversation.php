<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'organization_id', 'channel', 'external_id', 'started_at'
    ];
    protected $casts = [
        'started_at' => 'datetime',
        'meta'       => 'array',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
    public function messages()    { return $this->hasMany(Message::class); }
}
