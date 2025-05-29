<?php

namespace App\Traits;

use App\Models\Tag;

trait TaggableTrait
{
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}