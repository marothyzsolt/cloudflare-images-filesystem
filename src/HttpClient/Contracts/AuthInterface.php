<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts;

interface AuthInterface
{
    public function getHeaders(): array;
}