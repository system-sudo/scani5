<?php

namespace App\Models;

use App\Traits\LowercaseAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory,LowercaseAttributes;

    protected $fillable = [
        'user_id',
        'activity',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
