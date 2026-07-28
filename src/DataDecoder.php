<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\Exception\ExceptionInterface as DataUriExceptionInterface;
use OneToMany\DataUri\Contract\Record\DataUriInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\Helper\FilenameHelper;
use OneToMany\DataUri\Record\DataUri;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as FilesystemExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_diff;
use function basename;
use function ctype_print;
use function filesize;
use function filter_var;
use function fopen;
use function implode;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function is_writable;
use function parse_url;
use function preg_replace;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use function stream_get_wrappers;
use function strlen;
use function sys_get_temp_dir;
use function trim;

use const FILTER_VALIDATE_URL;
use const PHP_MAXPATHLEN;

final class DataDecoder
{
    private readonly string $tempDir;

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->tempDir = sys_get_temp_dir();

        if (!is_writable($this->tempDir)) {
            throw new InvalidArgumentException(sprintf('The temp dir "%s" is not writable.', $this->tempDir));
        }
    }

    public function decode(
        mixed $data,
        ?string $name = null,
        string|Type|null $type = null,
    ): DataUriInterface {
        if (!is_string($data) && !$data instanceof \Stringable) {
            throw new InvalidArgumentException('The data must be a non-NULL string or implement the "\Stringable" interface.');
        }

        if (empty($data = trim($data))) {
            throw new InvalidArgumentException('The data cannot be empty.');
        }

        $dataIsUrl = $dataIsFile = false;

        if (strlen($data) <= PHP_MAXPATHLEN) {
            if (is_file($data)) {
                $dataIsFile = true;
            } else {
                $dataIsUrl = false !== filter_var($data, FILTER_VALIDATE_URL);
            }
        }

        if (!$dataIsFile && is_dir($data)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (!ctype_print($data)) {
            throw new InvalidArgumentException('The data cannot contain non-printable, control, or NULL-terminated characters.');
        }

        if ($dataIsFile && !is_readable($data)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $data));
        }

        // Sanitize a user provieded name
        if ($name = trim((string) $name)) {
            // Replace multiple periods with a single period
            // and remove any unsafe characters from the name
            if ($name = preg_replace('/\.{2,}/', '.', $name)) {
                $name = preg_replace('/[^.A-Za-z0-9_-]/', '', $name);
            }
        }

        // Use the path for the name
        if (!$name && $dataIsFile) {
            $name = basename($data);
        }

        // Use the URL for the name
        if (!$name && $dataIsUrl) {
            $urlBits = parse_url($data);

            if (is_string($urlBits['path'] ?? null)) {
                $name = basename($urlBits['path']);
            }
        }

        $hasNonRandomDisplayName = !empty($name);

        try {
            /** @var non-empty-string $temporaryPath */
            $temporaryPath = Path::join($this->tempDir, '1tomany', FilenameHelper::generate(6), $name ?: FilenameHelper::generate(12));
        } catch (FilesystemExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating the temporary path failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        // Ensure we have a non-empty name
        if (!$name = basename($temporaryPath)) {
            throw new RuntimeException('An empty display name was generated.');
        }

        if ($dataIsFile) {
            try {
                // Copy the data to the temporary file
                $this->filesystem->copy($data, $temporaryPath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Copying the file "%s" to "%s" failed.', $data, $temporaryPath), previous: $e);
            }
        } else {
            // Ensure data, file, http, and https streams are registered
            $this->assertStreamsAreRegistered(['data', 'file', 'http', 'https']);

            // Read, decode, and stream the data
            if (!$stream = @fopen($data, 'rb')) {
                throw new InvalidArgumentException('Decoding the data stream failed.');
            }

            if (false === $contents = stream_get_contents($stream)) {
                throw new RuntimeException('Reading the stream contents failed.');
            }

            try {
                // Write the streamed data to the temporary file
                $this->filesystem->dumpFile($temporaryPath, $contents);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Writing the data to the file "%s" failed.', $temporaryPath), previous: $e);
            }
        }

        // Determine the file type
        if ($type && is_string($type)) {
            $type = Type::create($type);
        }

        if (!$type instanceof Type) {
            $type = Type::createFromPath($temporaryPath);
        }

        if ($extension = $type->getExtension()) {
            /** @var non-empty-string $name */
            $name = FilenameHelper::changeExtension($name, $extension);

            /** @var non-empty-string $filePath */
            $filePath = Path::join(Path::getDirectory($temporaryPath), $name);

            try {
                // Rename the temporary file with an extension
                $this->filesystem->rename($temporaryPath, $filePath, true);
            } catch (FilesystemExceptionInterface $e) {
                throw new RuntimeException(sprintf('Renaming "%s" to "%s" failed.', $temporaryPath, $filePath), previous: $e);
            }
        } else {
            $filePath = $temporaryPath;
        }

        // Ensure the filesize can be calculated
        if (false === $size = @filesize($filePath)) {
            throw new RuntimeException(sprintf('Reading the size of the file "%s" failed.', $filePath));
        }

        return new DataUri($filePath, $name, $size, $type, $hasNonRandomDisplayName ? $name : null); // ($dataIsUrl || $dataIsFile) ? $data : null);
    }

    public function decodeBase64(
        string $data,
        string|Type $format,
        ?string $name = null,
    ): DataUriInterface {
        return $this->decode(sprintf('data:%s;base64,%s', $format instanceof Type ? $format->getFormat() : $format, $data), $name, $format);
    }

    public function decodeText(
        string $text,
        string|Type $type = Type::Txt,
        ?string $name = null,
    ): DataUriInterface {
        if (!$type instanceof Type) {
            $type = Type::create($type);
        }

        if (!$type->isText()) {
            throw new InvalidArgumentException(sprintf('The type "%s" is not text.', $type->getName()));
        }

        if (null !== $name) {
            $name = trim($name);
        }

        try {
            $name = FilenameHelper::changeExtension($name ?: FilenameHelper::generate(12), $type->getExtension());
        } catch (DataUriExceptionInterface $e) {
            throw new RuntimeException(sprintf('Generating a temporary filename failed: %s.', rtrim($e->getMessage(), '.')), previous: $e);
        }

        return $this->decodeBase64(base64_encode($text), $type, $name);
    }

    /**
     * @param non-empty-list<non-empty-lowercase-string> $streams
     *
     * @throws RuntimeException when one or more streams are not registered with PHP
     */
    private function assertStreamsAreRegistered(array $streams): void
    {
        if ([] !== $missingStreams = array_diff($streams, stream_get_wrappers())) {
            throw new RuntimeException(sprintf('The following streams are not registered in this environment: "%s".', implode('", "', $missingStreams)));
        }
    }
}
