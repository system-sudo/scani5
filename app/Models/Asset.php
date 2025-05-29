<?php

namespace App\Models;

use App\Traits\{FilterableTrait, LowercaseAttributes, PaginateResults, SearchableTrait,SortableTrait,TaggableTrait};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{Model,SoftDeletes};
use Illuminate\Database\Eloquent\Casts\Attribute;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;
    use SearchableTrait;
    use SortableTrait;
    use TaggableTrait;
    use FilterableTrait;
    use LowercaseAttributes;
    use PaginateResults;

    protected $table = 'assets';

    protected $guarded = [];
    protected $appends = ['tag_value'];

    public function vulnerabilities()
    {
        return $this->belongsToMany(Vulnerability::class, table: 'vulnerables');
    }

    public function organization()
    {
        return $this->belongsTo(OrganizationModel::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')->select('tags.id as tag_id', 'tags.name');
    }

    public function tagValue(): Attribute
    {
        $tags = $this->tags()->get(['id', 'name']);
        return new Attribute(get: fn ($value) => $tags->map(fn ($tag) => [
            'id' => $tag->tag_id,
            'name' => $tag->name,
        ])->toArray());
    }
}
