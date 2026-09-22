<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    protected $fillable = ['slug', 'title', 'subtitle', 'body', 'meta_description'];
}
