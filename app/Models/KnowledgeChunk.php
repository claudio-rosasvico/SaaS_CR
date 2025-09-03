<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeChunk extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'source_id',
        'position',
        'content',
        'metadata',
        'embedding'
    ];
    protected $casts = [
        'metadata'  => 'array',
        'embedding' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
