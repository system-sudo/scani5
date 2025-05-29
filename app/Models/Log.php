<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\{SearchableTrait, SortableTrait, FilterableTrait, LowercaseAttributes, PaginateResults, DateFilterTrait};

class Log extends Model
{
    use HasFactory;
    use SearchableTrait;
    use SortableTrait;
    use FilterableTrait;
    use LowercaseAttributes;
    use PaginateResults;
    use DateFilterTrait;

    protected $guarded = [];

    public function role()
    {
        return $this->belongsTo(RoleModel::class);
    }
}
