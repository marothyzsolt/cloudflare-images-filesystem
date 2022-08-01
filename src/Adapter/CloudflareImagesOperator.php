<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Adapter;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use League\Flysystem\Config;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckExistence;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\BaseResponse;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\Image;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\ImageListResponse;
use Nette\NotImplementedException;
use Spatie\Once\Cache;

class CloudflareImagesOperator implements FilesystemOperator
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        if ($this->fileExists($location)) {
            return;
        }

        $this->httpClient->model(BaseResponse::class);
        $metadata = Arr::get($config, 'metadata');

        $fileInfo = finfo_open();

        $metadata['hash'] = md5($contents);
        $metadata['original_name'] = $location;
        $metadata['size'] = strlen($contents);
        $metadata['mime_type'] = finfo_buffer($fileInfo, $contents, FILEINFO_MIME_TYPE);

        Cache::getInstance()->flush();

        $this->httpClient->upload('images/v1', $contents, $location, [], ['metadata' => json_encode($metadata)]);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        throw new NotImplementedException();
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $data = $this->read($source);

        $this->write($destination, $data, (array) $this->getMetadata($source));
    }

    public function delete(string $location): void
    {
        $items = $this->findByName($location);
        foreach ($items as $item) {
            $this->httpClient->model(BaseResponse::class);
            $this->httpClient->delete('images/v1/' . $item->id);
        }

        Cache::getInstance()->flush();
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new NotImplementedException();
    }

    public function read(string $location): string
    {
        $image = $this->findByName($location)->first();
        $url = $image->getVariant(config('cloudflareimagesfilesystem.public_variant', 'public'));

        $client = new Client(['verify' => false]);

        return $client->get($url)->getBody()->getContents();
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        $this->httpClient->model(ImageListResponse::class);
        /** @var Image[] $imageModels */
        $imageModels = $this->httpClient->get('images/v1')->images;

        $files = [];
        foreach ($imageModels as $image) {
            $files[$image->id] = new FileAttributes($image->filename, $image->getSize(), 'public', $image->uploaded->timestamp, $image->getMimeType(), (array) $image->getMetadata());
        }

        return new DirectoryListing($files);
    }

    public function getMetadata(string $path): object
    {
        return $this->findByName($path)->first()->getMetadata();
    }

    public function getUrl(string $path): string
    {
        return $this->findByName($path)->first()->getVariant(config('cloudflareimagesfilesystem.public_variant', 'public')) ?? '';
    }

    private function findByName(string $path): iterable
    {
        return once(function () use ($path) {
            $this->httpClient->model(ImageListResponse::class);
            $response = $this->httpClient->get('images/v1');

            return collect($response->images)->where('filename', $path);
        });
    }


    public function fileExists(string $location): bool
    {
        Cache::getInstance()->flush();

        $this->httpClient->model(ImageListResponse::class);
        $item = $this->findByName($location)->first();

        return $item !== null;
    }

    public function directoryExists(string $location): bool
    {
        throw new NotImplementedException();
    }

    public function deleteDirectory(string $location): void
    {
        throw new NotImplementedException();
    }

    public function createDirectory(string $location, array $config = []): void
    {
        throw new NotImplementedException();
    }

    public function visibility(string $path): string
    {
        throw new NotImplementedException();
    }

    public function mimeType(string $path): string
    {
        return $this->findByName($path)->first()->getMimeType();
    }

    public function lastModified(string $path): int
    {
        return $this->findByName($path)->first()->uploaded->timestamp;
    }

    public function fileSize(string $path): int
    {
        return $this->findByName($path)->first()->getSize();
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function readStream(string $location)
    {
        throw new NotImplementedException();
    }

    public function has(string $location): bool
    {
        return $this->fileExists($location);
    }
}