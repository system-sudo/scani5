<?php

namespace App\Models;

use App\Traits\{LowercaseAttributes, SearchableTrait, SortableTrait,PaginateResults};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patch extends Model
{
    use HasFactory;
    use SearchableTrait;
    use SortableTrait;
    use LowercaseAttributes;
    use PaginateResults;
    protected $table = 'patch';
    protected $guarded = [];
}
