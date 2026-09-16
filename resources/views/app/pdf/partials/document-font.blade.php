@php
    $documentFontCompany = $company
        ?? ($payment->company ?? null)
        ?? ($estimate->company ?? null)
        ?? ($invoice->company ?? null);
    $documentFont = $documentFontCompany
        ? app(\App\Services\DocumentFontService::class)->for($documentFontCompany)
        : null;
    $documentFontUrl = function (string $file): string {
        if ($dompdfRendering ?? false) {
            return resource_path("static/fonts/{$file}");
        }

        return \Illuminate\Support\Facades\Vite::asset("resources/static/fonts/{$file}");
    };
@endphp

@if ($documentFont)
    <style>
        @font-face {
            font-family: "Poppins";
            font-style: normal;
            font-weight: 300;
            src: url("{{ $documentFontUrl('Poppins-Light.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Poppins";
            font-style: normal;
            font-weight: 400;
            src: url("{{ $documentFontUrl('Poppins-Regular.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Poppins";
            font-style: normal;
            font-weight: 500;
            src: url("{{ $documentFontUrl('Poppins-Medium.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Poppins";
            font-style: normal;
            font-weight: 600;
            src: url("{{ $documentFontUrl('Poppins-SemiBold.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Poppins";
            font-style: normal;
            font-weight: 900;
            src: url("{{ $documentFontUrl('Poppins-Black.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 300;
            src: url("{{ $documentFontUrl('Almarai-Light.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 400;
            src: url("{{ $documentFontUrl('Almarai-Regular.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 700;
            src: url("{{ $documentFontUrl('Almarai-Bold.ttf') }}") format("truetype");
        }

        @font-face {
            font-family: "Almarai";
            font-style: normal;
            font-weight: 800;
            src: url("{{ $documentFontUrl('Almarai-ExtraBold.ttf') }}") format("truetype");
        }

        body {
            font-family: {!! $documentFont['stack'] !!} !important;
        }
    </style>
@endif
