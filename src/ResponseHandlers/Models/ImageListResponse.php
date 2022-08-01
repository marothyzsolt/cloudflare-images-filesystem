<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models;

use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Attributes\ModelDescriber;

class ImageListResponse extends BaseResponse
{
    #[ModelDescriber(type: 'array', itemType: 'MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\Image')]
    public array $images;
}
