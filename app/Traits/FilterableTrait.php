<?php
namespace App\Traits;

trait FilterableTrait
{
    public function scopeFilter($query,  $filter) 
    {
        if ($filter) {
            foreach ($filter as $key => $value) {
                $query->whereIn($key, $value);
            }
        } else {
            return $query;
        }
    }
}