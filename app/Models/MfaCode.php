<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MfaCode extends Model
{
    use HasFactory;

    protected $table = 'temp_mfa_code';

    protected $fillable = [
        'user_id',
        'mfa_code',
    ];
}
