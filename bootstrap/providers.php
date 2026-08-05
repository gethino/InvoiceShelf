<?php

use App\Domains\Money\MoneyServiceProvider;
use App\Platform\Ai\AiServiceProvider;
use App\Platform\Mail\MailServiceProvider;
use App\Platform\Modules\ModuleServiceProvider;
use App\Platform\Operations\OperationsServiceProvider;
use App\Platform\Pdf\PdfServiceProvider;
use App\Platform\Storage\StorageServiceProvider;
use App\Providers\AppServiceProvider;
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
    MoneyServiceProvider::class,
    AiServiceProvider::class,
    MailServiceProvider::class,
    OperationsServiceProvider::class,
    ModuleServiceProvider::class,
    ScrambleServiceProvider::class,
];
