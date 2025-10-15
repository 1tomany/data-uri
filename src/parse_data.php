<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use function array_filter;
use function base64_encode;
use function basename;
use function bin2hex;
use function ctype_print;
use function explode;
use function fclose;
use function fopen;
use function hash_file;
use function implode;
use function is_dir;
use function is_file;
use function is_readable;
use function is_resource;
use function is_string;
use function is_writable;
use function mime_content_type;
use function parse_url;
use function pathinfo;
use function random_bytes;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function stream_get_contents;
use function stripos;
use function strtolower;
use function trim;
use function unlink;

use const FILEINFO_EXTENSION;
use const PATHINFO_EXTENSION;

/**
 * Parses data from a wide variety of sources into a SmartFile object.
 *
 * The `data` argument can be one of the following:
 * * A path to an existing readable file.
 * * An object that implements SmartFileInterface.
 * * A Data URL that follows the RFC 2397 standard (eg. "data:image/png;base64,R0lGOD...").
 * * An HTTP or HTTPS URL to a publicly accessible file.
 *
 * @param mixed $data the data to parse
 * @param ?string $name The original file name.
 *                      If empty, one will be generated.
 * @param ?string $directory The directory to save the file. If empty, the temporary directory will be used.
 * @param bool $deleteOriginal delete the original file if a file path is used
 * @param ?Filesystem $filesystem An instance of the Symfony Filesystem component. This is useful when you want to use parse_data() in your tests.
 *
 * @throws InvalidArgumentException
 */
function parse_data(
    mixed $data,
    ?string $name = null,
    ?string $directory = null,
    bool $deleteOriginal = false,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    if (!is_string($data) && !$data instanceof \Stringable) {
        throw new InvalidArgumentException('The data must be a non-NULL string or implement the "\Stringable" interface.');
    }

    if (empty($data = trim($data))) {
        throw new InvalidArgumentException('The data cannot be empty.');
    }

    $handle = null;

    try {
        if (is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable or NULL bytes.');
        }

        if (!is_writable($directory ??= sys_get_temp_dir())) {
            throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }

        if (is_file($data) && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // Resolve the File Name
        $name = trim($name ?? '');

        if (empty($name)) {
            if (is_file($data)) {
                $name = $data;
            } elseif (0 === stripos($data, 'http')) {
                $name = parse_url($data)['path'] ?? '';
            }
        }

        // Remove Path Prefix
        $name = basename($name);

        // Attempt to Read and Parse the Data
        if (!$handle = @fopen($data, 'rb')) {
            if (is_file($data)) {
                throw new InvalidArgumentException(sprintf('Failed to decode the file "%s".', $data));
            }

            throw new InvalidArgumentException('Failed to decode the data.');
        }

        $filesystem ??= new Filesystem();

        try {
            // Create Temporary File With Unique Prefix
            $temp = $filesystem->tempnam($directory, '__1n__datauri_');
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to create a file in "%s".', $directory), previous: $e);
        }

        try {
            if (false === $contents = stream_get_contents($handle)) {
                throw new RuntimeException('Failed to get the data contents.');
            }

            // Write Data to Temporary File
            $filesystem->dumpFile($temp, $contents);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to write data to file "%s".', $temp), previous: $e);
        }

        // Attempt to Resolve the Extension
        if (!$extension = pathinfo($name, PATHINFO_EXTENSION)) {
            $exts = new \finfo(FILEINFO_EXTENSION)->file($temp);

            if ($exts && !str_contains($exts, '?')) {
                $extension = explode('/', $exts)[0];
            }
        }

        try {
            // Rename the File With Extension
            $path = implode('.', array_filter([
                $temp, trim($extension, '?'),
            ]));

            $filesystem->rename($temp, $path, true);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to append extension "%s" to file "%s".', $extension, $temp), previous: $e);
        }

        if (false === $hash = hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $path));
        }

        // Resolve and Validate the Content Type
        $type = mime_content_type($path) ?: null;

        if (!$type || !str_contains($type, '/')) {
            throw new RuntimeException(sprintf('The type "%s" is invalid.', $type));
        }
    } finally {
        if (is_resource($handle)) {
            @fclose($handle);
        }

        if ($deleteOriginal) {
            @unlink($data);
        }
    }

    return new SmartFile($hash, $path, $name ?: null, $type, null, true, true);
}

/**
 * Parses data that is assumed to be base64 encoded, but not encoded as a Data URL.
 *
 * @throws InvalidArgumentException
 */
function parse_base64_data(
    string $data,
    string $type,
    ?string $name = null,
    ?string $directory = null,
    bool $deleteOriginal = false,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    return parse_data(sprintf('data:%s;base64,%s', $type, $data), $name, $directory, $deleteOriginal, $filesystem);
}

/**
 * Parses data that is assumed to be plaintext.
 *
 * @throws InvalidArgumentException
 */
function parse_text_data(
    string $text,
    ?string $name = null,
    ?string $directory = null,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    $extension = '.txt';

    if (!$name || !str_ends_with(strtolower(trim($name)), $extension)) {
        $name = implode('', [bin2hex(random_bytes(6)), $extension]);
    }

    return parse_base64_data(base64_encode($text), 'text/plain', $name, $directory, false, $filesystem);
}
