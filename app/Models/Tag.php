<?php

namespace App\Models;

use App\Traits\{LowercaseAttributes, PaginateResults, SearchableTrait, SortableTrait};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    use SearchableTrait;
    use SortableTrait;
    use LowercaseAttributes;
    use PaginateResults;

    protected $fillable = ['name', 'organization_id'];

    public function Asset()
    {
        return $this->morphedByMany(Asset::class, 'taggable');
    }

    public function Vulnerability()
    {
        return $this->morphedByMany(Vulnerability::class, 'taggable');
    }
}
