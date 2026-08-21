<?php

namespace App\Services\PDFDrivers;

use App\Services\PDFDriver;
use App\Services\ResponseStream;
use App\Support\ArabicPdfHtmlProcessor;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

final class DompdfPDFResponse implements ResponseStream
{
    public function __construct(private readonly PDF $pdf) {}

    public function stream(string $filename = 'document.pdf'): Response
    {
        return $this->pdf->stream($filename);
    }

    public function output(): string
    {
        return $this->pdf->output();
    }
}

final class DompdfPDFDriver implements PDFDriver
{
    public function __construct(private readonly ArabicPdfHtmlProcessor $arabicHtmlProcessor) {}

    public function loadView(string $template): ResponseStream
    {
        $html = view($template, ['dompdfRendering' => true])->render();
        $html = $this->arabicHtmlProcessor->process($html);
        $pdf = app('dompdf.wrapper')->loadHTML($html);

        return new DompdfPDFResponse($pdf);
    }
}
