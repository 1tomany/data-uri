<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\FileType;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface;
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
use function fopen;
use function hash_file;
use function implode;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function is_writable;
use function mime_content_type;
use function parse_url;
use function random_bytes;
use function rtrim;
use function sprintf;
use function str_ends_with;
use function stream_get_contents;
use function stripos;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function trim;

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

    $filesystem ??= new Filesystem();

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

        try {
            $tempFilePath = Path::join($directory, sprintf('1tomany_datauri_%s', bin2hex(random_bytes(6))));
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        // Resolve the display name
        if (!$displayName = trim($displayName ?? '')) {
            // The display name is the path from the URL
            if (!$isFile && 0 === stripos($data, 'http')) {
                $displayName = parse_url($data)['path'] ?? '';
            } elseif ($isFile) {
                $displayName = $data;
            }

            $displayName = basename($displayName ?: $tempFilePath);
        }

        if ($isFile) {
            try {
                // Copy the data file to the temporary file
                $filesystem->copy($data, $tempFilePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to copy "%s" to "%s".', $data, $tempFilePath), previous: $e);
            }
        } else {
            // Read, decode, and stream the data
            if (!$handle = @fopen($data, 'rb')) {
                throw new InvalidArgumentException('Failed to decode the data.');
            }

            if (false === $contents = stream_get_contents($handle)) {
                throw new RuntimeException('Failed to get the data contents.');
            }

            try {
                // Write the data to the temporary file
                $filesystem->dumpFile($tempFilePath, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Failed to write data to "%s".', $tempFilePath), previous: $e);
            }
        }

        // Attempt to determine the file format
        if (!$fileFormat = @mime_content_type($tempFilePath)) {
            throw new RuntimeException('Failed to determine the file format.');
        }

        $fileType = FileType::create($fileFormat);

        try {
            /** @var non-empty-string */
            $displayName = Path::changeExtension($displayName, $fileType->getExtension() ?? '');

            /** @var non-empty-string $filePath */
            $filePath = Path::join($directory, $displayName);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating a temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        try {
            $filesystem->rename($tempFilePath, $filePath, true);
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Failed to rename "%s" to "%s".', $tempFilePath, $filePath), previous: $e);
        }

        if (!$fileHash = hash_file('sha256', $filePath)) {
            throw new RuntimeException(sprintf('Failed to calculate a hash of the file "%s".', $filePath));
        }

        // Validate minimum hash length
        if (strlen($fileHash) < SmartFileInterface::MINIMUM_HASH_LENGTH) {
            throw new RuntimeException(sprintf('The hash "%s" must be %d or more characters.', $fileHash, SmartFileInterface::MINIMUM_HASH_LENGTH));
        }

        try {
            // Begin by bucketing the file into directories based on the hash
            $remoteKeyDirectory = substr($fileHash, 0, 2).'/'.substr($fileHash, 2, 2);

            // Generate a random string to use as the remote key
            $remoteKeySuffix = new \Random\Randomizer()->getBytesFromString(SmartFileInterface::REMOTE_KEY_ALPHABET, 10);

            // Append the extension to the suffix
            if (null !== $ext = $fileType->getExtension()) {
                $remoteKeySuffix = Path::changeExtension($remoteKeySuffix, $ext);
            }

            // Append the randomly generated suffix to create the remote key
            $remoteKey = implode('/', [$remoteKeyDirectory, $remoteKeySuffix]);
        } catch (\Random\RandomException|\Random\RandomError $e) {
            throw new RuntimeException('Failed to generate a sufficiently random remote key suffix.', previous: $e);
        }
    } catch (ExceptionInterface $e) {
        try {
            $tempFilesToDelete = [];

            // Compile a list of files to delete
            if (is_string($filePath ?? null)) {
                $tempFilesToDelete[] = $filePath;
            }

            if (is_string($tempFilePath ?? null)) {
                $tempFilesToDelete[] = $tempFilePath;
            }

            $filesystem->remove($tempFilesToDelete);
        } catch (FilesystemExceptionInterface $e) {
        }

        throw $e;
    }

    try {
        // Delete the original file
        if ($deleteOriginal && $isFile) {
            $filesystem->remove($data);
        }
    } catch (FilesystemExceptionInterface $e) {
    }

    return new SmartFile($fileHash, $filePath, $displayName, $fileType->getExtension(), $fileType, strtolower($fileFormat), filesize($filePath) ?: 0, $remoteKey, $selfDestruct);
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
