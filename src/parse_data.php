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
use function preg_match;
use function random_bytes;
use function rtrim;
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
 * Parses file data into a SmartFileInterface object.
 *
 * This method takes a wide variety of file data and attempts to create a
 * temporary self-destructing file that implements the SmartFileInterface
 * interface. The data can be an existing file, a publicly accessible URL,
 * or a Data URL ("data:image/png;base64,R0lGOD...") defined by RFC 2397.
 *
 * @param mixed       $data           The data to parse: an existing file, a public URL, or an RFC 2397 Data URL
 * @param ?string     $displayName    Display name for the temporary file; a random name is generated if empty
 * @param ?string     $directory      Directory to create the temporary file in, sys_get_temp_dir() is used if empty
 * @param bool        $deleteOriginal If true and $data is a file, the file will be deleted after the SmartFile object is created
 * @param bool        $selfDestruct   If true, the SmartFile object will delete the temporary file it references when destructed
 * @param ?Filesystem $filesystem     An instance of the Symfony Filesystem component used for mocks in tests
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
        $isFile = is_file($data);

        if (!$isFile && is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable or NULL bytes.');
        }

        // Resolve the directory to save the temporary file in
        $directory = trim($directory ?? '') ?: sys_get_temp_dir();

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }

        if ($isFile && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // Resolve the file name
        $displayName = trim($displayName ?? '');

        if (!$displayName) {
            if ($isFile) {
                $displayName = $data;
            } elseif (0 === stripos($data, 'http')) {
                $displayName = parse_url($data)['path'] ?? '';
            }
        }

        $filesystem ??= new Filesystem();

        try {
            // Create temporary file with unique prefix
            $tempFilePath = $filesystem->tempnam($directory, '__1n__datauri_');
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to create a file in "%s".', $directory), previous: $e);
        }

        if (!$isFile) {
            // Attempt to decode the file data
            if (!$handle = @fopen($data, 'rb')) {
                throw new InvalidArgumentException('Failed to decode the data.');
            }

            try {
                if (false === $contents = stream_get_contents($handle)) {
                    throw new RuntimeException('Failed to get the data contents.');
                }

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

        // Attempt to resolve the extension
        $displayName = basename($displayName);

        if (!$extension = pathinfo($displayName, PATHINFO_EXTENSION)) {
            $extensions = new \finfo(FILEINFO_EXTENSION)->file($tempFilePath);

            if ($extensions && !str_contains($extensions, '?')) {
                $extension = explode('/', $extensions)[0];
            } else {
                $extension = null;
            }
        }

        $extension = $extension ? strtolower($extension) : null;

        // Rename the temporary file with the extension
        $filePath = rtrim($tempFilePath.'.'.$extension, '.');

        try {
            $filesystem->rename($tempFilePath, $filePath, true);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to append extension "%s" to file "%s".', $extension, $tempFilePath), previous: $e);
        }

        if (false === $hash = hash_file('sha256', $filePath)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $filePath));
        }

        // Resolve and validate the MIME type
        $mimeType = mime_content_type($filePath) ?: null;

        if (!$mimeType) {
            throw new RuntimeException('Failed to resolve a MIME type for the data.');
        }

        if (!preg_match(SmartFileInterface::MIME_TYPE_REGEX, $mimeType)) {
            throw new InvalidArgumentException(sprintf('The MIME type "%s" is invalid.', $mimeType));
        }

        $smartFile = new SmartFile($hash, $filePath, $displayName ?: null, $mimeType, null, true, $selfDestruct);
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
 * Parses data that is assumed to be base64 encoded, but not encoded as a Data URL.
 *
 * @throws InvalidArgumentException
 * @throws RuntimeException
 */
function parse_base64_data(
    string $data,
    string $mimeType,
    ?string $displayName = null,
    ?string $directory = null,
    bool $selfDestruct = true,
    ?Filesystem $filesystem = null,
): SmartFileInterface {
    if (!preg_match(SmartFileInterface::MIME_TYPE_REGEX, $mimeType)) {
        throw new InvalidArgumentException(sprintf('The MIME type "%s" is invalid.', $mimeType));
    }

    return parse_data(sprintf('data:%s;base64,%s', $mimeType, $data), $displayName, $directory, false, $selfDestruct, $filesystem);
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

    if (!$displayName || !str_ends_with(strtolower(trim($displayName)), $extension)) {
        $displayName = implode('', [bin2hex(random_bytes(6)), $extension]);
    }

    return parse_base64_data(base64_encode($text), 'text/plain', $displayName, $directory, $selfDestruct, $filesystem);
}
