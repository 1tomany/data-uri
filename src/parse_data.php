<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function array_filter;
use function basename;
use function ctype_print;
use function explode;
use function fopen;
use function hash_file;
use function is_dir;
use function is_writable;
use function parse_url;
use function pathinfo;
use function sprintf;
use function str_contains;
use function stream_get_contents;
use function trim;

use const FILEINFO_EXTENSION;
use const PATHINFO_EXTENSION;

function parse_data(
    ?string $data,
    ?string $name = null,
    ?string $type = null,
    ?string $dir = null,
    bool $delete = false,
    ?Filesystem $filesystem = null,
): SmartFile {
    if (empty($data = trim($data ?? ''))) {
        throw new InvalidArgumentException('The data cannot be empty.');
    }

    if (is_dir($data)) {
        throw new InvalidArgumentException('The data cannot be a directory.');
    }

    if (!ctype_print($data)) {
        throw new InvalidArgumentException('The data cannot contain non-printable or NULL bytes.');
    }

    if (!is_writable($dir ??= sys_get_temp_dir())) {
        throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $dir));
    }

    // Resolve the File Name
    $name = trim($name ?? '');

    if (empty($name) && is_file($data)) {
        // Resolve the Name From URLs or Paths
        $name = parse_url($data)['path'] ?? '';
    }

    // Remove Path Prefix
    $name = basename($name);

    // Normalize the Content MIME Type
    $type = strtolower(trim($type ?? ''));

    // Attempt to Read and Parse the Data
    if (!$handle = @fopen($data, 'rb')) {
        if (empty($type)) {
            throw new InvalidArgumentException('The type cannot be empty when decoding data.');
        }

        $data = sprintf('data:%s;base64,%s', $type, $data);

        if (!$handle = @fopen($data, 'rb')) {
            throw new InvalidArgumentException('really cant read this thing');
        }
    }

    $filesystem ??= new Filesystem();

    try {
        // Create a temporary file with a unique prefix
        $temp = $filesystem->tempnam($dir, '__1n__datauri_');

        if (false === $contents = stream_get_contents($handle)) {
            throw new RuntimeException('Reading contents failed.');
        }

        // Write the data to the temporary file
        $filesystem->dumpFile($temp, $contents);

        // Attempt to Resolve the Extension
        if (!$extension = pathinfo($name, PATHINFO_EXTENSION)) {
            $exts = new \finfo(FILEINFO_EXTENSION)->file($temp);

            if ($exts && str_contains($exts, '?')) {
                $extension = explode('/', $exts)[0];
            }
        }

        // Rename the File With Extension
        $path = implode('.', array_filter([
            $temp, trim($extension, '?'),
        ]));

        $filesystem->rename($temp, $path, true);

        if (false === $hash = hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $path));
        }

        // Resolve and Validate the Content Type
        $type = $type ?: mime_content_type($path);

        if (!$type || !str_contains($type, '/')) {
            throw new RuntimeException(sprintf('The type "%s" is invalid.', $type));
        }
    } catch (FilesystemExceptionInterface $e) {
        throw new RuntimeException('Failed to parse the data.', previous: $e);
    } finally {
        @fclose($handle);

        if (true === $delete) {
            @unlink($data);
        }
    }

    return new SmartFile($hash, $path, $name ?: null, null, $type, true, true);
}

/*
function parse_base64_data(string $data, ?string $type = null, ?string $name = null): SmartFile
{
    if (!empty($type = trim($type ?? ''))) {
        $data = sprintf('data:%s;base64,%s', $type, $data);
    }
}
*/
