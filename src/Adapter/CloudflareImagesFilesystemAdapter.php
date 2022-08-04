<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\Adapter;

use Closure;
use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use MarothyZsolt\CloudflareImagesFileSystem\Exceptions\MustInstanceOfException;
use MarothyZsolt\CloudflareImagesFileSystem\RequestHandlers\Models\PutFile;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin \League\Flysystem\FilesystemOperator
 */
class CloudflareImagesFilesystemAdapter extends FilesystemAdapter implements CloudFilesystemContract
{
    public function put($path, $contents, $options = [])
    {
        $options = is_string($options)
                     ? ['visibility' => $options]
                     : (array) $options;

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
        if ($contents instanceof File ||
            $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            if (is_resource($contents)) {
                $this->driver->writeStream($path, $contents, $options);
            }

            return $this->driver->writeWithResponse($path, $contents, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    public function putMultiple(iterable $items): iterable
    {
        foreach ($items as $item) {
            if (! $item instanceof PutFile) {
                throw new MustInstanceOfException(PutFile::class);
            }
        }

        return $this->driver->writeMultiple($items);
    }
}
