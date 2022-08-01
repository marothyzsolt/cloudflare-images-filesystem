<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts;

use Illuminate\Http\Client\Response;

interface ResponseModelInterface
{
    public function getResponse(): Response;
}