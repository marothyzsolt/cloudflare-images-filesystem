<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Models;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Image
{
    public string $id;

    public string $filename;

    public Carbon $uploaded;

    public bool $requireSignedURLs;

    public array $variants = [];

    public object $metadata;

    public function getVariant(string $name): ?string
    {
        if (Arr::exists($this->variants, $name)) {
            return $this->variants[$name];
        }

        return null;
    }

    public function getVariants(): array
    {
        return array_keys($this->variants);
    }

    public function prepareInit(object $data): void
    {
        $data->uploaded = Carbon::createFromTimeString($data->uploaded);
    }

    public function postInit(): void
    {
        $this->metadata = (object) [];

        $this->initVariants();
        $this->initMetadata();
    }

    private function initVariants(): void
    {
        $variants = [];

        foreach ($this->variants as $variant) {
            $variants[(string) Str::of($variant)->afterLast('/')] = $variant;
        }

        $this->variants = $variants;
    }

    private function initMetadata(): void
    {
        if (property_exists($this, 'meta') && property_exists($this->meta, 'metadata')) {
            $this->metadata = json_decode($this->meta->metadata);
        }
    }

    public function getMetadata(): object
    {
        return $this->metadata;
    }

    public function getSize(): int
    {
        return $this->metadata?->size ?? 0;
    }

    public function getMimeType(): string
    {
        return $this->metadata?->mime_type ?? 'image/jpeg';
    }
}
