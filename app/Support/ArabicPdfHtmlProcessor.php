<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

final class ArabicPdfHtmlProcessor
{
    public function __construct(private readonly Arabic $arabic) {}

    public function process(string $html): string
    {
        if ($html === '' || preg_match('/\p{Arabic}/u', $html) !== 1) {
            return $html;
        }

        $positions = $this->arabic->arIdentify($html, true);

        for ($index = count($positions) - 1; $index > 0; $index -= 2) {
            $start = $positions[$index - 1];
            $length = $positions[$index] - $start;
            $segment = substr($html, $start, $length);
            $maximumCharacters = max(50, mb_strlen($segment) + 1);
            $shapedSegment = $this->arabic->utf8Glyphs(
                $segment,
                $maximumCharacters,
                false,
                true,
            );

            $html = substr_replace($html, $shapedSegment, $start, $length);
        }

        return $html;
    }
}
