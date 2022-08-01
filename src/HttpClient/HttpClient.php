<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\AuthInterface;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Exceptions\ResponseException;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseDtoGeneratorInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseModelInterface;

class HttpClient implements HttpClientInterface
{
    private PendingRequest $client;

    private ?string $responseModel = null;

    public function __construct(private AuthInterface $auth, private ?string $baseUri = null, private float $timeout = 30.0)
    {
        if ($this->baseUri === null) {
            $this->baseUri = 'https://api.cloudflare.com/client/v4/';
        }

        $this->resetClient();
    }

    public function get(string $uri, array $data = [], array $headers = []): ResponseModelInterface
    {
        return $this->request('get', $uri, $data, $headers);
    }

    public function post(string $uri, array $data = [], array $headers = []): ResponseModelInterface
    {
        return $this->request('post', $uri, $data, $headers);
    }

    public function put(string $uri, array $data = [], array $headers = []): ResponseModelInterface
    {
        return $this->request('put', $uri, $data, $headers);
    }

    public function patch(string $uri, array $data = [], array $headers = []): ResponseModelInterface
    {
        return $this->request('patch', $uri, $data, $headers);
    }

    public function delete(string $uri, array $data = [], array $headers = []): ResponseModelInterface
    {
        return $this->request('delete', $uri, $data, $headers);
    }

    public function upload(string $uri, string $file, string $filename, array $headers = [], array $additionalData = []): ResponseModelInterface
    {
        $body = [
            'file' => [
                'Content-type' => 'multipart/form-data',
                'name' => 'file',
                'contents' => $file,
                'filename' => $filename,
            ],
            'metadata' => json_encode($additionalData),
        ];

        $response = $this->client
            ->asMultipart()
            ->withHeaders($headers)
            ->post($uri, $body);

        $response->onError(function (Response $response) {
            throw new \Exception($response->body());
        });

        $responseModel = app()->make($this->responseModel);

        $this->resetClient();

        return app(ResponseDtoGeneratorInterface::class)->generate($responseModel, $response);
    }

    public function request(string $method, string $uri, array $data = [], array $headers = [], array $additionalData = []): ResponseModelInterface
    {
        if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            throw new \InvalidArgumentException('Request method must be get, post, put, patch, or delete');
        }

        if (count($additionalData) === 0) {
            $additionalData = [($method === 'get' ? 'query' : 'json') => $data];
            $this->client->asJson();
        }

        $response = $this->client->$method($uri, $additionalData);
        $response->onError(function (Response $response) {
            throw new \Exception($response->body());
        });

        $this->resetClient();

        return app(ResponseDtoGeneratorInterface::class)->generate(app()->make($this->responseModel), $response);
    }

    public function model(string $responseModel): HttpClientInterface
    {
        $this->responseModel = $responseModel;

        return $this;
    }

    private function resetClient(): void
    {
        unset($this->client);

        $this->client = Http::withHeaders($this->auth->getHeaders())
            ->timeout($this->timeout)
            ->accept('application/json')
            ->withoutVerifying()
            ->baseUrl($this->baseUri);
    }
}
