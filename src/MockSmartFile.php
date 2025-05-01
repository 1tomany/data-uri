<?php

namespace OneToMany\DataUri;

use function array_rand;
use function bin2hex;
use function hash;
use function max;
use function random_bytes;
use function random_int;
use function strval;
use function sys_get_temp_dir;
use function trim;
use function vsprintf;

final readonly class MockSmartFile extends SmartFile
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
        ?string $filePath = null,
        ?string $fingerprint = null,
        ?string $mediaType = null,
        ?int $byteCount = null,
    ) {
        $byteCount = $byteCount ?? random_int(
            self::MIN_BYTES, self::MAX_BYTES
        );

        $byteCount = max(0, $byteCount);

        $fingerprint = $fingerprint ?? hash(
            'sha256', random_bytes(self::MIN_BYTES)
        );

        $filePath = trim(strval($filePath));

        if (empty($filePath)) {
            $extension = array_rand(self::MIME_TYPES);

            $fileName = vsprintf('%s.%s', [
                bin2hex(random_bytes(8)), $extension,
            ]);

            $filePath = vsprintf('%s/%s', [
                sys_get_temp_dir(), $fileName,
            ]);

            $mediaType = self::MIME_TYPES[$extension];
        }

        if (null === $mediaType) {
            $mediaType = self::MIME_TYPES[
                array_rand(self::MIME_TYPES)
            ];
        }

        parent::__construct($filePath, $fingerprint, $mediaType, null, $byteCount, false, false);
    }

    public function __destruct()
    {
    }
}
