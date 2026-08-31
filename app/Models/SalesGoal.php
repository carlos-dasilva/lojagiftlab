<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesGoal extends Model
{
    protected $fillable = ['period_type', 'target_amount', 'effective_from'];

    protected $casts = ['target_amount' => 'decimal:2', 'effective_from' => 'date'];
}
