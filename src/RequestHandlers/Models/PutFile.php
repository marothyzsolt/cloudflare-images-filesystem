<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\RequestHandlers\Models;

class PutFile
{
    public function __construct(private string $path, private string $content, private array $config = [])
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
