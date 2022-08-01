<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts;

use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseModelInterface;

interface HttpClientInterface
{
    public function __construct(AuthInterface $auth, string $baseUri = null, float $timeout = 30.0);

    public function get(string $uri, array $data = [], array $headers = []): ResponseModelInterface;

    public function post(string $uri, array $data = [], array $headers = []): ResponseModelInterface;

    public function put(string $uri, array $data = [], array $headers = []): ResponseModelInterface;

    public function patch(string $uri, array $data = [], array $headers = []): ResponseModelInterface;

    public function delete(string $uri, array $data = [], array $headers = []): ResponseModelInterface;

    public function upload(string $uri, string $file, string $filename, array $headers = [], array $additionalData = []): ResponseModelInterface;

    public function model(string $responseModel): HttpClientInterface;
}