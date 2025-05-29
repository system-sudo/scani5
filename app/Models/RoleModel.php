<?php

namespace App\Models;

use App\Traits\LowercaseAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HasFactory;
    use LowercaseAttributes;
    protected $table = 'roles';

    protected $guarded = [];
}
