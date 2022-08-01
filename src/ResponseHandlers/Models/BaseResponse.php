<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models;

use Illuminate\Http\Client\Response;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseModelInterface;

class BaseResponse implements ResponseModelInterface
{
    public object $result;

    public bool $success;

    public array $errors;

    public array $messages;

    public Response $response;

    public function getResponse(): Response
    {
        return $this->response;
    }
}
