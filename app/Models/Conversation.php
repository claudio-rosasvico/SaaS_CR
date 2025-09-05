<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToOrganization;

class Conversation extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;
    
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
