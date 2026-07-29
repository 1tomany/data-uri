<?php

namespace OneToMany\DataUri\Source;

use OneToMany\DataUri\Contract\Source\SourceResolverInterface;
use OneToMany\DataUri\Contract\Source\UrlPolicyInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;
use OneToMany\DataUri\MediaType;

use function array_shift;
use function basename;
use function fclose;
use function fopen;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function parse_url;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function str_starts_with;
use function strcasecmp;
use function stream_context_create;
use function stream_get_meta_data;
use function strtolower;
use function substr;
use function trim;

use const FILTER_VALIDATE_URL;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

final readonly class SourceResolver implements SourceResolverInterface
{
    public function __construct(
        private UrlPolicyInterface $urlPolicy = new PublicUrlPolicy(),
        private float $remoteTimeout = 10.0,
    ) {
        if ($this->remoteTimeout <= 0) {
            throw new InvalidArgumentException('The remote timeout must be greater than zero.');
        }
    }

    public function resolve(string|\Stringable $data): ResolvedSource
    {
        $rawData = (string) $data;

        if ('' === trim($rawData)) {
            throw new InvalidArgumentException('The data cannot be empty.');
        }

        $data = trim($rawData);

        if (preg_match('/[\x00-\x1F\x7F]/', $data)) {
            throw new InvalidArgumentException('The data cannot contain control or NULL characters.');
        }

        if (str_starts_with(strtolower($data), 'data:')) {
            return $this->resolveDataUri($data);
        }

        if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $data)) {
            $scheme = strtolower((string) parse_url($data, PHP_URL_SCHEME));

            return match ($scheme) {
                'file' => $this->resolveFileUrl($data),
                'http', 'https' => $this->resolveRemoteUrl($data),
                default => throw new InvalidArgumentException(sprintf('The stream scheme "%s" is not supported.', $scheme)),
            };
        }

        if (is_dir($rawData)) {
            throw new InvalidArgumentException('The data cannot be a directory.');
        }

        if (is_file($rawData)) {
            return $this->resolveLocalFile($rawData);
        }

        throw new InvalidArgumentException(sprintf('The file "%s" does not exist or is not readable.', $data));
    }

    private function resolveLocalFile(string $path): ResolvedSource
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $path));
        }

        $suggestedName = basename($path);

        return new ResolvedSource(
            sprintf('file "%s"', $path),
            '' !== $suggestedName ? $suggestedName : null,
            static function () use ($path): OpenedSource {
                if (!$stream = @fopen($path, 'rb')) {
                    throw new RuntimeException(sprintf('Opening the file "%s" failed.', $path));
                }

                return new OpenedSource($stream);
            },
        );
    }

    private function resolveFileUrl(string $url): ResolvedSource
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || '' === $path) {
            throw new InvalidArgumentException(sprintf('The file URL "%s" does not contain a path.', $url));
        }

        return $this->resolveLocalFile(rawurldecode($path));
    }

    private function resolveDataUri(string $data): ResolvedSource
    {
        $comma = strpos($data, ',');

        if (false === $comma) {
            throw new InvalidArgumentException('The data URI does not contain a data separator.');
        }

        $header = substr($data, 5, $comma - 5);
        $declaredMediaType = $this->parseDataUriMediaType($header);

        return new ResolvedSource(
            'data URI',
            null,
            static function () use ($data, $declaredMediaType): OpenedSource {
                if (!$stream = @fopen($data, 'rb')) {
                    throw new InvalidArgumentException('Decoding the data URI failed.');
                }

                return new OpenedSource($stream, $declaredMediaType);
            },
        );
    }

    private function resolveRemoteUrl(string $url): ResolvedSource
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('The URL "%s" is invalid.', $url));
        }

        $this->urlPolicy->assertAllowed($url);
        $path = parse_url($url, PHP_URL_PATH);
        $suggestedName = is_string($path) && '' !== basename($path) ? rawurldecode(basename($path)) : null;
        $timeout = $this->remoteTimeout;

        return new ResolvedSource(
            sprintf('URL "%s"', $url),
            $suggestedName,
            static function () use ($url, $timeout): OpenedSource {
                $context = stream_context_create([
                    'http' => [
                        'follow_location' => 0,
                        'ignore_errors' => false,
                        'max_redirects' => 0,
                        'timeout' => $timeout,
                        'user_agent' => '1tomany/data-uri',
                    ],
                    'ssl' => [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ],
                ]);

                if (!$stream = @fopen($url, 'rb', false, $context)) {
                    throw new RuntimeException(sprintf('Opening the URL "%s" failed.', $url));
                }

                try {
                    [$status, $mediaType] = self::readHttpMetadata($stream);

                    if ($status < 200 || $status >= 300) {
                        throw new RuntimeException(sprintf('Opening the URL "%s" returned HTTP status %d.', $url, $status));
                    }

                    return new OpenedSource($stream, $mediaType);
                } catch (\Throwable $e) {
                    fclose($stream);

                    throw $e;
                }
            },
        );
    }

    private function parseDataUriMediaType(string $header): string
    {
        $parts = explode(';', $header);
        $baseType = trim((string) array_shift($parts));
        $parameters = [];

        foreach ($parts as $parameter) {
            if (0 === strcasecmp('base64', trim($parameter))) {
                continue;
            }

            $parameters[] = trim($parameter);
        }

        $format = '' !== $baseType ? $baseType : 'text/plain';

        if ([] !== $parameters) {
            $format .= ';'.implode(';', $parameters);
        }

        return MediaType::fromString($format)->value;
    }

    /**
     * @param resource $stream
     *
     * @return array{positive-int, ?non-empty-string}
     */
    private static function readHttpMetadata(mixed $stream): array
    {
        $metadata = stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];
        $status = null;
        $mediaType = null;

        foreach (is_array($headers) ? $headers : [$headers] as $header) {
            if (!is_string($header)) {
                continue;
            }

            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                $status = (int) $matches[1];
            } elseif (str_starts_with(strtolower($header), 'content-type:')) {
                $mediaType = trim(substr($header, 13));
            }
        }

        if (null === $status || $status < 100) {
            throw new RuntimeException('Reading the HTTP response status failed.');
        }

        return [$status, '' !== $mediaType ? $mediaType : null];
    }
}
