<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts;

use Illuminate\Http\Client\Response;

interface ResponseDtoGeneratorInterface
{
    public function generate(ResponseModelInterface $responseModel, Response $response): ResponseModelInterface;
}
