<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Tests\Feature;

use MarothyZsolt\CloudflareImagesFileSystem\Tests\TestCase;

class StorageListingTest extends TestCase
{
    /** @test */
    public function cloudflare_connection_test(): void
    {
        //Storage::disk('images')->put('bloo.jpg', file_get_contents(__DIR__ . '\..\bloo.jpg'));
        //$e = Storage::disk('images')->move('bloo.jpg', 'COPIED.'.Str::random(6).'.jpg');
        //$e = Storage::disk('images')->delete('bloo.jpg');
        //$e = Storage::disk('images')->getMetadata('bloo.jpg');

        $this->assertTrue(true);
    }
}