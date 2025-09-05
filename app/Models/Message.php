<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToOrganization;

class Message extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;
    
    protected $fillable = [
        'organization_id', 'conversation_id', 'role', 'content', 'meta'
    ];
    protected $casts = [
        'meta' => 'array',
    ];

    public function conversation(){ return $this->belongsTo(Conversation::class); }
}
