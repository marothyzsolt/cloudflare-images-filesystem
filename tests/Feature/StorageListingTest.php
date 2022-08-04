<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MarothyZsolt\CloudflareImagesFileSystem\RequestHandlers\Models\PutFile;
use MarothyZsolt\CloudflareImagesFileSystem\Tests\TestCase;

class StorageListingTest extends TestCase
{
    /** @test */
    public function cloudflare_connection_test(): void
    {
        config()->set('filesystems.disks.images', [
            'driver' => 'cloudflare-images',
            'account_id' => config('cloudflareimagesfilesystem.cf_account_id'),
            'api_email' => config('cloudflareimagesfilesystem.api_email'),
            'api_key' => config('cloudflareimagesfilesystem.api_key'),
        ]);


        //$image = Storage::disk('images')->put('blooav.jpg', file_get_contents(__DIR__ . '\..\..\bloo.jpg'));
        //Storage::disk('images')->deleteDirectory('/');
        //Storage::disk('images')->putMultiple([
        //    new PutFile(path: 'blooav3.jpg', content: file_get_contents(__DIR__ . '\..\..\bloo.jpg')),
        //    new PutFile(path: 'blooav4.jpg', content: file_get_contents(__DIR__ . '\..\..\bloo.jpg')),
        //]);
        //dd($image->getUrl());
        //$e = Storage::disk('images')->copy('bloo.jpg', 'COPIED.'.Str::random(6).'.jpg');
        //$e = Storage::disk('images')->delete('bloo.jpg');
        //$e = Storage::disk('images')->getMetadata('bloo.jpg');
        //$e = Storage::disk('images')->delete('COPIED.LHxqaa.jpg');

        $this->assertTrue(true);
    }
}