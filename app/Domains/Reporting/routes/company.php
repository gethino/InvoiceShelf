<?php

use App\Domains\Reporting\Http\Controllers\Company\CustomerStatementController;
use App\Domains\Reporting\Http\Controllers\Company\SendCustomerStatementController;
use Illuminate\Support\Facades\Route;

Route::get('customers/{customer}/statement', CustomerStatementController::class);
Route::post('customers/{customer}/statement/send', SendCustomerStatementController::class);
