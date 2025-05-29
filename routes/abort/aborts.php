<?php

use Illuminate\Support\Facades\Route;

Route::get('/trigger-500', function () {
            abort(500);
        });
