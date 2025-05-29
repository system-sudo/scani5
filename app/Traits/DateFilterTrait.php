<?php

namespace App\Traits;

trait DateFilterTrait
{
    public function scopeDateRangeFilter($query, $column, $start, $end)
    {
        if ($column && $start && $end) {
            $query->whereBetween($column, [$start, $end]);
        } else {
            return $query;
        }
    }
}
