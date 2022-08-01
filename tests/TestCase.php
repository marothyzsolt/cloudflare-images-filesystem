<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Tests;

use MarothyZsolt\CloudflareImagesFileSystem\CloudflareImagesFileSystemServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            CloudflareImagesFileSystemServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }
}