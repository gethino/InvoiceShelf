<?php

namespace App\Space;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageUtils
{
    /**
     * Convert local path to Base64 encoded data source
     *
     * @return string
     */
    public static function toBase64Src($path)
    {
        return sprintf('data:%s;base64,%s', File::mimeType($path), base64_encode(File::get($path)));
    }

    public static function toBase64SrcFromStorage(string $disk, string $path, string $mimeType): ?string
    {
        try {
            $contents = Storage::disk($disk)->get($path);
        } catch (Throwable) {
            return null;
        }

        if ($contents === null) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
    }
}
