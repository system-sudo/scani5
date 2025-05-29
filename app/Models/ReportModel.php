<?php

namespace App\Models;

use App\Traits\{LowercaseAttributes, PaginateResults, SearchableTrait, SortableTrait};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ReportModel extends Model
{
    use HasFactory;
    use SearchableTrait;
    use SortableTrait;
    use LowercaseAttributes;
    use PaginateResults;
    protected $table = 'reports';

    protected $guarded = [];

    protected $appends = ['creator_name'];

    protected $hidden = ['created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creatorName(): Attribute
    {
        $creator = $this->creator()->pluck('name');
        return new Attribute(get: fn ($value) => ($creator) ? $creator[0] : null, );
    }

    public function organization()
    {
        return $this->hasOne(OrganizationModel::class, 'id', 'organization_id');
    }
}
