<?php

namespace App\Traits;

trait PaginateResults
{
    public function scopePaginateResults($query, $default = 10)
    {
        $limit = (int) request('limit', $default);

        if ($limit < 10) {
            $limit = 10;
        } elseif ($limit > 30) {
            $limit = 30;
        } else {
            $limit = (int) ceil($limit / 10) * 10;
        }

        return $query->paginate($limit);
    }
}
