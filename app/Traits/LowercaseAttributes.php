<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait LowercaseAttributes
{
    protected static array $excludedFields = ['password', 'token', 'mfa_token'];

    public static function bootLowercaseAttributes()
    {
        static::saving(function (Model $model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && !in_array($key, self::$excludedFields, true)) {
                    $model->{$key} = strtolower($value);
                }
            }
        });
    }
}
