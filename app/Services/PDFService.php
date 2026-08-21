<?php

namespace App\Services;

/*
 * Two options:
 *  - Barryvdh\DomPDF\Facade\Pdf
 *  - Gotenberg
*/

use App;
use App\Services\PDFDrivers\DompdfPDFDriver;
use App\Services\PDFDrivers\GotenbergPDFDriver;
use Illuminate\Http\Response;

interface ResponseStream
{
    public function stream(string $filename = 'document.pdf'): Response;

    public function output(): string;
}

interface PDFDriver
{
    public function loadView(string $template): ResponseStream;
}

class PDFDriverFactory
{
    public static function create(string $driver)
    {
        return match ($driver) {
            'dompdf' => App::make(DompdfPDFDriver::class),
            'gotenberg' => new GotenbergPDFDriver,
            default => throw new \InvalidArgumentException('Invalid PDFDriver requested')
        };
    }
}

class PDFService
{
    public static function loadView(string $template)
    {
        $driver = config('pdf.driver');

        return PDFDriverFactory::create($driver)->loadView($template);
    }
}
