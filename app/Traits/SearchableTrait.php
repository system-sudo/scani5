<?php

namespace App\Traits;

trait SearchableTrait
{
    public function scopeSearch($query, $search, $columns, $json = false, $json_column = null)
    {
        if (!empty($search)) {
            $search = trim($search);
            return $query->where(function ($subquery) use ($search, $columns, $json, $json_column) {
                foreach ($columns as $column) {
                    $subquery->orWhere($column, 'like', '%' . $search . '%');
                    if ($json) {
                        foreach ($json_column as $key => $value) {
                            $subquery->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT($key, '$.$value')) LIKE ?", ['%' . $search . '%']);
                        }
                    }
                }
            });
        } else {
            return $query;
        }
    }
}
