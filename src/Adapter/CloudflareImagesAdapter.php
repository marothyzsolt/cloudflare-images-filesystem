<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Adapter;

use GuzzleHttp\Client;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\BaseResponse;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\ImageListResponse;
use Nette\NotImplementedException;
use Spatie\Once\Cache;

class CloudflareImagesAdapter implements FilesystemAdapter
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function write(string $path, string $contents, Config $config): void
    {
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
    }

    public function delete(string $path): void
    {
    }

    public function setVisibility($path, $visibility): void
    {
    }

    public function read($path): string
    {
    }

    public function fileExists(string $path): bool
    {
    }

    public function directoryExists(string $path): bool
    {
    }

    public function deleteDirectory(string $path): void
    {
    }

    public function createDirectory(string $path, Config $config): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
    }

    public function mimeType(string $path): FileAttributes
    {
    }

    public function lastModified(string $path): FileAttributes
    {
    }

    public function fileSize(string $path): FileAttributes
    {
    }

    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function readStream(string $path)
    {
    }

    public function listContents(string $path, bool $deep): iterable
    {
    }
}