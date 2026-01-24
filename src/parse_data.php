<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Record\SmartFileInterface;
use OneToMany\DataUri\Exception\AssertValidMimeType;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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

use const FILEINFO_EXTENSION;
use const PATHINFO_EXTENSION;

/**
 * Parses file data into a `SmartFileInterface` object.
 *
 * This method takes data from a wide variety of sources and attempts
 * to create a temporary self-destructing file represented by an object
 * that implements `SmartFileInterface`. The data can be an existing
 * file, a public URL, or a data URL defined by RFC 2397 ("data:...").
 *
 * @param mixed $data The data to parse: an existing file, a public URL, or an RFC 2397 Data URL
 * @param ?string $displayName Display name for the temporary file; a random name is generated if empty
 * @param ?string $directory Directory to create the temporary file in, sys_get_temp_dir() is used if empty
 * @param bool $deleteOriginal If true and $data is a file, the file will be deleted after parse_data() runs
 * @param bool $selfDestruct If true, the temporary file will be deleted when the object that references it is destroyed
 * @param ?Filesystem $filesystem An instance of the Symfony Filesystem component used for mocks in tests
 *
 * @throws InvalidArgumentException if $data is not a string
 * @throws InvalidArgumentException if $data is empty
 * @throws InvalidArgumentException if $data is a directory
 * @throws InvalidArgumentException if $data contains non-printable text, control characters, or NUL bytes
 * @throws InvalidArgumentException if $directory is not null and not writable, or the temp directory is not writable
 * @throws InvalidArgumentException if $data is a file but is not readable
 * @throws InvalidArgumentException if $data decoding the data fails
 * @throws RuntimeException when creating a temporary file fails
 * @throws RuntimeException when reading from a stream fails
 * @throws RuntimeException when writing the temporary file fails
 * @throws RuntimeException the sha256 hash of the file could not be calculated
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
        $isFile = is_file($data);

        if (!$isFile && is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable, control, or NULL characters.');
        }

        // Resolve the directory to save the temporary file in
        $directory = trim($directory ?? '') ?: sys_get_temp_dir();

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }

        if ($isFile && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // Resolve the display name
        $displayName = trim($displayName ?? '');

        if (!$displayName) {
            if ($isFile) {
                $displayName = basename($data);
            } elseif (0 === stripos($data, 'http')) {
                $displayName = parse_url($data)['path'] ?? '';
            }
        }

        $displayName = $displayName ?: sprintf('__1n__datauri_%s', bin2hex(random_bytes(6)));
        var_dump($displayName);

        $tempFilePath = Path::join($directory, $displayName);
        // if (!empty($displayName)) {
        //     $tempFilePath = Path::join($directory, $displayName);
        // } else {
        //     $tempFilePath = Path::join($directory, );
        // }

        $filesystem ??= new Filesystem();

        if (!$isFile) {
            // Attempt to decode the data
            if (!$handle = @fopen($data, 'rb')) {
                throw new InvalidArgumentException('Failed to decode the data.');
            }

            if (false === $contents = stream_get_contents($handle)) {
                throw new RuntimeException('Failed to get the data contents.');
            }

            // try {
            //     $tempFilePath = $filesystem->tempnam($directory, '__1n__datauri_');
            // } catch (FilesystemExceptionInterface $e) {
            //     throw new RuntimeException(sprintf('Failed to create a temporary file in "%s".', $directory), previous: $e);
            // }

            try {
                // Write data to the temporary file
                $filesystem->dumpFile($tempFilePath, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to write data to "%s".', $tempFilePath), previous: $e);
            }
        } else {
            try {
                // Copy the source file to the temporary file
                $filesystem->copy($data, $tempFilePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to copy "%s" to "%s".', $data, $tempFilePath), previous: $e);
            }
        }

        // Resolve the extension from the display name or file contents
        if (!$extension = pathinfo($displayName, PATHINFO_EXTENSION)) {
            $extensions = new \finfo(FILEINFO_EXTENSION)->file($tempFilePath);

            if ($extensions && !str_contains($extensions, '?')) {
                $extension = explode('/', $extensions)[0];
            }
        }

        // Ensure the extension is lowercase or null
        $extension = strtolower($extension ?: '') ?: null;

        // Rename the temporary file with the extension
        if (!empty($extension)) {
            $filePath = $tempFilePath.'.'.$extension;

            try {
                $filesystem->rename($tempFilePath, $filePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to rename "%s" to "%s".', $tempFilePath, $filePath), previous: $e);
            }
        } else {
            $filePath = $tempFilePath;
        }

        if (false === $hash = hash_file('sha256', $filePath)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $filePath));
        }

        // Attempt to resolve the file format
        if (!$format = mime_content_type($filePath)) {
            throw new RuntimeException('Failed to resolve the file format.');
        }

        $smartFile = new SmartFile($hash, $filePath, $displayName, AssertValidMimeType::assert($format), null, true, $selfDestruct);
    } finally {
        if (is_resource($handle)) {
            @fclose($handle);
        }
    }

    try {
        // Delete the original file
        if ($deleteOriginal && $isFile) {
            $filesystem->remove($data);
        }
    } catch (FilesystemExceptionInterface $e) {
    }

    return $smartFile;
}

/**
 * Parses data that is assumed to be base64 encoded.
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 */
function parse_base64_data(
    string $data,
    string $format,
    ?string $displayName = null,
    ?string $directory = null,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    return parse_data(sprintf('data:%s;base64,%s', AssertValidMimeType::assert($format), $data), $displayName, $directory, false, $selfDestruct, $filesystem);
}

/**
 * Parses data that is assumed to be plaintext.
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 */
function parse_text_data(
    string $text,
    ?string $displayName = null,
    ?string $directory = null,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    $extension = '.txt';

    // Generate a display name for the file if one wasn't provided
    $displayName = trim($displayName ?? '');

    if (!$displayName || !str_ends_with(strtolower($displayName), $extension)) {
        $displayName = implode('', [bin2hex(random_bytes(6)), $extension]);
    }

    return parse_base64_data(base64_encode($text), 'text/plain', $displayName, $directory, $selfDestruct, $filesystem);
}
