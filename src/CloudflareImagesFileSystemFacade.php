<?php

namespace MarothyZsolt\CloudflareImagesFileSystem;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarothyZsolt\CloudflareImagesFileSystem\Skeleton\SkeletonClass
 */
class CloudflareImagesFileSystemFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cloudflareimagesfilesystem';
    }
}
