<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

it('installs calendar and packages PDF templates outside persistent storage', function () {
    $productionDockerfile = File::get(base_path('docker/production/Dockerfile'));
    $developmentDockerfile = File::get(base_path('docker/development/Dockerfile'));
    $dockerignore = File::get(base_path('.dockerignore'));

    expect($productionDockerfile)
        ->toContain('RUN install-php-extensions calendar')
        ->toContain('storage/app/templates/pdf/ /opt/invoiceshelf/pdf-templates/');

    expect($developmentDockerfile)->toContain('RUN install-php-extensions calendar');

    expect($dockerignore)
        ->toContain('!storage/app/templates/')
        ->toContain('!storage/app/templates/**');
});

it('syncs managed PDF templates while preserving unrelated custom templates', function () {
    $temporaryRoot = sys_get_temp_dir().'/invoiceshelf-template-sync-'.bin2hex(random_bytes(8));
    $source = $temporaryRoot.'/source';
    $target = $temporaryRoot.'/target';

    try {
        File::ensureDirectoryExists($source.'/invoice');
        File::ensureDirectoryExists($target.'/invoice');
        File::put($source.'/invoice/managed.blade.php', 'managed-v2');
        File::put($source.'/invoice/preview.png', 'preview-v2');
        File::put($target.'/invoice/managed.blade.php', 'managed-v1');
        File::put($target.'/invoice/custom.blade.php', 'custom');

        $firstRun = runTemplateSync($source, $target);
        $secondRun = runTemplateSync($source, $target);

        expect($firstRun->isSuccessful())->toBeTrue()
            ->and($secondRun->isSuccessful())->toBeTrue()
            ->and(File::get($target.'/invoice/managed.blade.php'))->toBe('managed-v2')
            ->and(File::get($target.'/invoice/preview.png'))->toBe('preview-v2')
            ->and(File::get($target.'/invoice/custom.blade.php'))->toBe('custom');
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});

it('fails clearly when bundled PDF templates are missing', function () {
    $temporaryRoot = sys_get_temp_dir().'/invoiceshelf-template-sync-'.bin2hex(random_bytes(8));

    try {
        $process = runTemplateSync($temporaryRoot.'/missing', $temporaryRoot.'/target');

        expect($process->isSuccessful())->toBeFalse()
            ->and($process->getErrorOutput())->toContain('Bundled PDF templates not found');
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});

function runTemplateSync(string $source, string $target): Process
{
    $process = new Process(
        ['bash', base_path('docker/production/entrypoint.d/02-sync-pdf-templates.sh')],
        base_path(),
        [
            'PDF_TEMPLATE_SOURCE_DIR' => $source,
            'PDF_TEMPLATE_TARGET_DIR' => $target,
        ],
    );
    $process->run();

    return $process;
}
