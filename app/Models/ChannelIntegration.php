<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelIntegration extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'channel', 'enabled', 'config'];
    protected $casts = ['config' => 'array'];
}
