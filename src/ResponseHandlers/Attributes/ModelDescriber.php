<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Attributes;

#[\Attribute]
class ModelDescriber
{
    public function __construct(public string $type, public string $itemType)
    {
    }
}