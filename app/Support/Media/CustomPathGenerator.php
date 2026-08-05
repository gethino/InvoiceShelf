<?php

namespace App\Support\Media;

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Platform\Persistence\ModelIdentityMap;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversations/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    /*
     * Get a unique base path for the given media.
     */
    protected function getBasePath(Media $media): string
    {
        $folderName = null;

        if ($media->model_type === ModelIdentityMap::aliasFor(Invoice::class)) {
            $folderName = 'Invoices';
        } elseif ($media->model_type === ModelIdentityMap::aliasFor(Estimate::class)) {
            $folderName = 'Estimates';
        } elseif ($media->model_type === ModelIdentityMap::aliasFor(Payment::class)) {
            $folderName = 'Payments';
        } else {
            $folderName = $media->getKey();
        }

        return $folderName;
    }
}
