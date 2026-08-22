<?php

use Illuminate\Support\Facades\Route;
use Modules\TripoliCustomizations\Http\Controllers\QuickLoginController;

Route::post('quick-login', QuickLoginController::class)
    ->middleware(['guest', 'throttle:20,1'])
    ->name('tripoli.quick-login');
