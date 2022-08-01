<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Adapter;

use GuzzleHttp\Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\BaseResponse;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models\ImageListResponse;
use Nette\NotImplementedException;
use Spatie\Once\Cache;

class CloudflareImagesAdapter implements AdapterInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function write($path, $contents, Config $config)
    {
        $this->httpClient->model(BaseResponse::class);
        $metadata = $config->get('metadata');

        $fileInfo = finfo_open();

        $metadata['hash'] = md5($contents);
        $metadata['original_name'] = $path;
        $metadata['size'] = strlen($contents);
        $metadata['mime_type'] = finfo_buffer($fileInfo, $contents, FILEINFO_MIME_TYPE);

        Cache::getInstance()->flush();

        return $this->httpClient->upload('images/v1', $contents, $path, [], ['metadata' => json_encode($metadata)]);
    }

    public function writeStream($path, $resource, Config $config)
    {
        throw new NotImplementedException();
    }

    public function update($path, $contents, Config $config)
    {
        $this->delete($path);

        return $this->write($path, $contents, $config);
    }

    public function updateStream($path, $resource, Config $config)
    {
        throw new NotImplementedException();
    }

    public function rename($path, $newpath)
    {
        $this->copy($path, $newpath);
        $this->delete($path);
    }

    public function copy($path, $newpath)
    {
        $data = $this->read($path);

        $config = new Config((array) $data['meta']);
        $this->write($newpath, $data['contents'], $config);
    }

    public function delete($path)
    {
        $items = $this->findByName($path);
        foreach ($items as $item) {
            $this->httpClient->model(BaseResponse::class);
            $this->httpClient->delete('images/v1/' . $item->id);
        }

        Cache::getInstance()->flush();

        return true;
    }

    public function deleteDir($dirname)
    {
        throw new NotImplementedException();
    }

    public function createDir($dirname, Config $config)
    {
        throw new NotImplementedException();
    }

    public function setVisibility($path, $visibility)
    {
        throw new NotImplementedException();
    }

    public function has($path)
    {
        Cache::getInstance()->flush();

        $this->httpClient->model(ImageListResponse::class);
        $item = $this->findByName($path)->first();

        if ($item !== null) {
            return $item;
        }

        return false;
    }

    public function read($path)
    {
        $image = $this->findByName($path)->first();
        $url = $image->getVariant(config('cloudflareimagesfilesystem.public_variant', 'public'));

        $client = new Client(['verify' => false]);
        $e = $client->get($url);

        return ['contents' => $e->getBody()->getContents(), 'meta' => $image->getMetadata()];
    }

    public function readStream($path)
    {
        throw new NotImplementedException();
    }

    public function listContents($directory = '', $recursive = false)
    {
        $this->httpClient->model(ImageListResponse::class);

        return $this->httpClient->get('images/v1');
    }

    public function getMetadata($path)
    {
        return $this->findByName($path)->first()->getMetadata();
    }

    public function getSize($path)
    {
        return ['size' => $this->findByName($path)->first()->getSize()];
    }

    public function getMimetype($path)
    {
        return ['mimetype' => $this->findByName($path)->first()->getMimeType()];
    }

    public function getTimestamp($path)
    {
        return ['timestamp' => $this->findByName($path)->first()->uploaded->timestamp];
    }

    public function getVisibility($path)
    {
        throw new NotImplementedException();
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
}