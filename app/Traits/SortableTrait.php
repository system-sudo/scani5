<?php
namespace App\Traits;

trait SortableTrait
{
    public function scopeSort($query,  $column_name, $direction, $accepted_columns) 
    {
        if (!empty($column_name) && !empty($direction) && in_array($column_name, $accepted_columns)) {
            return $query->orderBy($column_name, $direction);
        }
    
        return $query;
    }
}