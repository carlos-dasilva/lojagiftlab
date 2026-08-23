<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['desktop_image', 'mobile_image', 'title', 'subtitle', 'button_label', 'url', 'starts_at', 'ends_at', 'active', 'order'];

    protected $casts = ['active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
}
