<?php

use Illuminate\Support\Facades\Route;
use Modules\TripoliCustomizations\Http\Controllers\CustomerOrganizationController;
use Modules\TripoliCustomizations\Http\Controllers\CustomizationSettingsController;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::get('tripoli-customizations/settings', [CustomizationSettingsController::class, 'show']);
        Route::put('tripoli-customizations/settings', [CustomizationSettingsController::class, 'update']);

        Route::get('customer-organizations', [CustomerOrganizationController::class, 'index']);
        Route::post('customer-organizations', [CustomerOrganizationController::class, 'store']);
        Route::put('customer-organizations/{organization}', [CustomerOrganizationController::class, 'update']);
        Route::delete('customer-organizations/{organization}', [CustomerOrganizationController::class, 'destroy']);
        Route::put('customer-organizations/{organization}/members', [CustomerOrganizationController::class, 'syncMembers']);
    });
