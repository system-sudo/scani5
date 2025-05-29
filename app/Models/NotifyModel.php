<?php

namespace App\Models;

use App\Traits\PaginateResults;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class NotifyModel extends Model
{
    use HasFactory;
    use PaginateResults;

    protected $table = 'notify';

    protected $guarded = [];

    protected $appends = ['is_read'];

    public function isRead(): Attribute
    {
        return new Attribute(get: fn ($value) => $this->read_at ? true : false);
    }
}
