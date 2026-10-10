<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(config('app.frontend_url')));
