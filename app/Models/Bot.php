<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id','name','channel','config'];
    protected $casts = [
        'config' => 'array',
    ];

    public function organization(){ return $this->belongsTo(Organization::class); }
}
