<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use League\Flysystem\Config;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckExistence;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\RequestHandlers\Models\PutFile;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\BaseResponse;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\Image;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\ImageListResponse;
use Nette\NotImplementedException;

class CloudflareImagesOperator implements FilesystemOperator
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->writeWithResponse($location, $contents, $config);
    }

    public function writeWithResponse(string $location, string $contents, array $config = []): object
    {
        if ($file = $this->findById($location)) {
            return $file;
        }

        $metadata = Arr::get($config, 'metadata');

        $fileInfo = finfo_open();

        $metadata['hash'] = md5($contents);
        $metadata['original_name'] = $location;
        $metadata['size'] = strlen($contents);
        $metadata['mime_type'] = finfo_buffer($fileInfo, $contents, FILEINFO_MIME_TYPE);

        $this->httpClient->model(Image::class);
        return $this->httpClient->upload('images/v1', $contents, $location, [], ['metadata' => json_encode($metadata)]);
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
        $this->httpClient->model(BaseResponse::class);
        $this->httpClient->delete('images/v1/' . $location);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new NotImplementedException();
    }

    public function read(string $location): string
    {
        $image = $this->findById($location);
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
        return $this->findById($path)->getMetadata();
    }

    public function getUrl(string $path): string
    {
        return Cache::remember('cf-images-' . md5($path), now()->addMinutes(config('cloudflareimagesfilesystem.url_cache_time', 1200)), function () use ($path) {
            return $this->findById($path)->getVariant(config('cloudflareimagesfilesystem.public_variant', 'public')) ?? '';
        });
    }

    private function findById(string $id): ?object
    {
        $this->httpClient->model(Image::class);
        try {
            return $this->httpClient->get('images/v1/' . $id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function fileExists(string $location): bool
    {
        $this->httpClient->model(ImageListResponse::class);
        $item = $this->findById($location);

        return $item !== null;
    }

    public function directoryExists(string $location): bool
    {
        throw new NotImplementedException();
    }

    public function deleteDirectory(string $location): void
    {
        if ($location === '/') {
            $this->truncateImages();

            return;
        }

        throw new NotImplementedException();
    }

    public function truncateImages(): void
    {
        $this->httpClient->model(ImageListResponse::class);
        $imageModels = $this->httpClient->get('images/v1')->images;

        $this->httpClient->async(function (PendingRequest $client) use ($imageModels): iterable {
            foreach ($imageModels as $image) {
                yield $client->async()->delete('images/v1/' . $image->id);
            }
        });

        if (count($imageModels) > 0) {
            $this->truncateImages();
        }
    }

    public function writeMultiple(iterable $items): iterable
    {
        return $this->httpClient->async(function (PendingRequest $client) use ($items): iterable {
            /** @var PutFile $putFile */
            foreach ($items as $putFile) {
                $client->async();
                yield $this->httpClient->upload(
                    'images/v1',
                    $putFile->getContent(),
                    $putFile->getPath(),
                    [],
                    ['metadata' => json_encode($putFile->getConfig())],
                    $client
                );
            }
        });
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
        return $this->findById($path)->getMimeType();
    }

    public function lastModified(string $path): int
    {
        return $this->findById($path)->uploaded->timestamp;
    }

    public function fileSize(string $path): int
    {
        return $this->findById($path)->getSize();
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