<?php

namespace OneToMany\DataUri;

use function array_rand;
use function bin2hex;
use function hash;
use function random_bytes;
use function random_int;
use function sys_get_temp_dir;

final readonly class MockDataUri extends DataUri
{
    private const int MIN_BYTES = 1024; // 1KB
    private const int MAX_BYTES = 4_194_304; // 4MB

    private const array MIME_TYPES = [
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
    ];

    public function __construct(
        ?string $fingerprint = null,
        ?string $mediaType = null,
        ?int $byteCount = null,
        ?string $filePath = null,
        ?string $fileName = null,
        ?string $extension = null,
        ?string $remoteKey = null,
    ) {
        $fingerprint = $fingerprint ?? hash(
            'sha256', random_bytes(16)
        );

        $byteCount = $byteCount ?? random_int(
            self::MIN_BYTES, self::MAX_BYTES
        );

        $extension ??= array_rand(self::MIME_TYPES);
        $mediaType ??= self::MIME_TYPES[$extension];

        $fileName = $fileName ?? implode('.', [
            bin2hex(random_bytes(8)), $extension,
        ]);

        $filePath = $filePath ?? implode('/', [
            sys_get_temp_dir(), $fileName,
        ]);

        $remoteKey = $remoteKey ?? implode('/', [
            $fingerprint[0], $fingerprint[1], $fileName,
        ]);

        parent::__construct($fingerprint, $mediaType, $byteCount, $filePath, $fileName, $extension, $remoteKey);
    }

    public function __destruct()
    {
    }
}
