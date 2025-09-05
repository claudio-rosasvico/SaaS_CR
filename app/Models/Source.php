<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToOrganization;

class Source extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'type',
        'title',
        'url',
        'text_content',
        'storage_path',
        'status',
        'meta'
    ];
    protected $casts = [
        'meta' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
