<?php

use App\Platform\Ai\AiServiceProvider;
use App\Platform\Mail\MailServiceProvider;
use App\Platform\Modules\ModuleServiceProvider;
use App\Platform\Pdf\PdfServiceProvider;
use App\Platform\Storage\StorageServiceProvider;
use App\Providers\AppConfigProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DriverRegistryProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\ScrambleServiceProvider;
use App\Providers\ViewServiceProvider;
use App\Support\Hashids\HashidsServiceProvider;

return [
    HashidsServiceProvider::class,
    AppServiceProvider::class,
    RouteServiceProvider::class,
    StorageServiceProvider::class,
    ViewServiceProvider::class,
    PdfServiceProvider::class,
    DriverRegistryProvider::class,
    AiServiceProvider::class,
    MailServiceProvider::class,
    AppConfigProvider::class,
    ModuleServiceProvider::class,
    ScrambleServiceProvider::class,
];
