<?php

namespace MarothyZsolt\CloudflareImagesFileSystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use MarothyZsolt\CloudflareImagesFileSystem\Adapter\CloudflareImagesAdapter;
use MarothyZsolt\CloudflareImagesFileSystem\Adapter\CloudflareImagesOperator;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\AuthApikey;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\AuthInterface;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\Contracts\HttpClientInterface;
use MarothyZsolt\CloudflareImagesFileSystem\HttpClient\HttpClient;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseDtoGeneratorInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\ResponseDtoGenerator;

class CloudflareImagesFileSystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cloudflareimagesfilesystem.php' => config_path('cloudflareimagesfilesystem.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cloudflareimagesfilesystem.php', 'cloudflareimagesfilesystem');

        $this->app->bind(AuthInterface::class, function () {
            return new AuthApikey(
                config('cloudflareimagesfilesystem.api_email'),
                config('cloudflareimagesfilesystem.api_key'),
            );
        });

        $this->app->bind(HttpClientInterface::class, function () {
            $uri = config('cloudflareimagesfilesystem.base_uri') . config('cloudflareimagesfilesystem.cf_account_id') . '/';
            return new HttpClient(
                app()->make(AuthInterface::class),
                $uri,
            );
        });

        $this->app->bind(ResponseDtoGeneratorInterface::class, function () {
            return new ResponseDtoGenerator();
        });

        Storage::extend('cloudflare-images', function(Application $app, array $config)
        {
            $authProvider = $this->app->make(AuthInterface::class, [
                'api_email' => $config['api_email'],
                'api_key' => $config['api_key'],
            ]);

            $client = $this->app->make(HttpClientInterface::class, ['auth', $authProvider]);

            return new FilesystemAdapter(new CloudflareImagesOperator($client), new CloudflareImagesAdapter($client));
        });
    }
}
