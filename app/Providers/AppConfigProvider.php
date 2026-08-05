<?php

namespace App\Providers;

use App\Platform\Mail\Contracts\MailConfigurator;
use App\Platform\Pdf\Contracts\PdfConfigurator;
use App\Platform\Storage\Application\FileDiskService;
use App\Platform\Storage\Models\FileDisk;
use App\Support\Setup\InstallUtils;
use Illuminate\Support\ServiceProvider;

class AppConfigProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Check if database is available
        if (! InstallUtils::isDbCreated()) {
            return;
        }

        $this->configureMailFromDatabase();
        $this->configurePdfFromDatabase();
        $this->configureFileSystemFromDatabase();
    }

    /**
     * Configure mail settings from database
     */
    protected function configureMailFromDatabase(): void
    {
        try {
            app(MailConfigurator::class)->applyGlobalConfig();
        } catch (\Exception $e) {
            // Silently fail if database is not available (during installation, migrations, etc.)
            // This prevents the application from breaking during setup
        }
    }

    /**
     * Configure PDF settings from database
     */
    protected function configurePdfFromDatabase(): void
    {
        try {
            app(PdfConfigurator::class)->applyGlobalConfig();
        } catch (\Exception $e) {
            // Silently fail if database is not available (during installation, migrations, etc.)
            // This prevents the application from breaking during setup
        }
    }

    /**
     * Configure file system settings from database
     */
    protected function configureFileSystemFromDatabase(): void
    {
        try {
            $fileDisk = FileDisk::whereSetAsDefault(true)->first();

            if (! $fileDisk) {
                return;
            }

            $diskName = app(FileDiskService::class)->registerDisk($fileDisk);

            // Point Spatie Media Library at the resolved disk
            config(['media-library.disk_name' => $diskName]);
        } catch (\Exception $e) {
            // Silently fail if database is not available (during installation, migrations, etc.)
        }
    }
}
