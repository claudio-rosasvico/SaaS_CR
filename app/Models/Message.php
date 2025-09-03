<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'organization_id', 'conversation_id', 'role', 'content', 'meta'
    ];
    protected $casts = [
        'meta' => 'array',
    ];

    public function conversation(){ return $this->belongsTo(Conversation::class); }
}
