<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOrganization;

class Organization extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = ['name'];

    // Relaciones útiles
    public function bots()         { return $this->hasMany(Bot::class); }
    public function sources()      { return $this->hasMany(Source::class); }
    public function conversations(){ return $this->hasMany(Conversation::class); }
}
