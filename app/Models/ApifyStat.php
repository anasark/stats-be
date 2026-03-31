<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform', 'data', 'fetched_at'])]
class ApifyStat extends Model
{
    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
