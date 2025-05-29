<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TotpRegenerate extends Model
{
    use HasFactory;

    protected $table = 'totp_regeneration';
    protected $guarded = [];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
