<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

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
use function sys_get_temp_dir;
use function trim;
use function unlink;

use const FILEINFO_EXTENSION;
use const PATHINFO_EXTENSION;

/**
 * Parses file data into a SmartFileInterface object.
 *
 * This method takes a wide variety of file data and attempts to create a
 * temporary self-destructing file that implements the SmartFileInterface
 * interface. The data can be an existing file, a publicly accessible URL,
 * or a data URL ("data:image/png;base64,R0lGOD...") defined by RFC 2397.
 *
 * @param mixed       $data           The data to parse: an existing file, a public URL, or a data URL
 * @param ?string     $displayName    Display name for the temporary file; a random name is generated if empty
 * @param ?string     $directory      Directory to create the temporary file in, sys_get_temp_dir() is used if empty
 * @param bool        $deleteOriginal If true and $data is a file, the file will be deleted after the `SmartFile` object is created
 * @param bool        $selfDestruct   If true, the SmartFile object will delete the temporary file it references when destructed
 * @param ?Filesystem $filesystem     An instance of the Symfony Filesystem component for mocks in tests
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 *
 * @author Vic Cherubini <vcherubini@gmail.com>
 */
function parse_data(
    mixed $data,
    ?string $displayName = null,
    ?string $directory = null,
    bool $deleteOriginal = false,
    bool $selfDestruct = true,
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

        // Resolve the file name
        $displayName = trim($displayName ?? '');

        if (empty($displayName)) {
            if (is_file($data)) {
                $displayName = $data;
            } elseif (0 === stripos($data, 'http')) {
                $displayName = parse_url($data)['path'] ?? '';
            }
        }

        $displayName = basename($displayName);

        // Attempt to parse the file data
        if (!$handle = @fopen($data, 'rb')) {
            if (is_file($data)) {
                throw new InvalidArgumentException(sprintf('Failed to decode the file "%s".', $data));
            }

            throw new InvalidArgumentException('Failed to decode the data.');
        }

        $filesystem ??= new Filesystem();

        try {
            // Create temporary file with unique prefix
            $temp = $filesystem->tempnam($directory, '__1n__datauri_');
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to create a file in "%s".', $directory), previous: $e);
        }

        try {
            if (false === $contents = stream_get_contents($handle)) {
                throw new RuntimeException('Failed to get the data contents.');
            }

            // Write data to the temporary file
            $filesystem->dumpFile($temp, $contents);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to write data to file "%s".', $temp), previous: $e);
        }

        // Attempt to resolve the extension
        if (!$extension = pathinfo($displayName, PATHINFO_EXTENSION)) {
            $exts = new \finfo(FILEINFO_EXTENSION)->file($temp);

            if ($exts && !str_contains($exts, '?')) {
                $extension = explode('/', $exts)[0];
            } else {
                $extension = null;
            }
        }

        // Rename the file with extension
        $path = !empty($extension) ? $temp.'.'.strtolower($extension) : $temp;

        try {
            $filesystem->rename($temp, $path, true);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to append extension "%s" to file "%s".', $extension, $temp), previous: $e);
        }

        if (false === $hash = hash_file('sha256', $path)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $path));
        }

        // Resolve and validate the MIME type
        $mimeType = mime_content_type($path) ?: null;

        if (!$mimeType || !str_contains($mimeType, '/')) {
            throw new RuntimeException(sprintf('The MIME type "%s" is invalid.', $mimeType));
        }

        $smartFile = new SmartFile($hash, $path, $displayName ?: null, $mimeType, null, true, $selfDestruct);
    } finally {
        if (is_resource($handle)) {
            @fclose($handle);
        }
    }

    if ($deleteOriginal && is_file($data)) {
        @unlink($data);
    }

    return $smartFile;
}

/**
 * Parses data that is assumed to be base64 encoded, but not encoded as a Data URL.
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 */
function parse_base64_data(
    string $data,
    string $type,
    ?string $name = null,
    ?string $directory = null,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    return parse_data(sprintf('data:%s;base64,%s', $type, $data), $name, $directory, false, $selfDestruct, $filesystem);
}

/**
 * Parses data that is assumed to be plaintext.
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 */
function parse_text_data(
    string $text,
    ?string $name = null,
    ?string $directory = null,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    $extension = '.txt';

    if (!$name || !str_ends_with(strtolower(trim($name)), $extension)) {
        $name = implode('', [bin2hex(random_bytes(6)), $extension]);
    }

    return parse_base64_data(base64_encode($text), 'text/plain', $name, $directory, $selfDestruct, $filesystem);
}
