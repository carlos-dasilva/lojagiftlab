<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    protected $fillable = ['question', 'answer', 'category', 'order', 'active'];

    protected $casts = ['active' => 'boolean'];
}
