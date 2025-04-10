<?php

namespace OneToMany\DataUri;

final readonly class MockDataUri extends DataUri
{
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
        $bytes = \bin2hex(\random_bytes(16));

        $fingerprint ??= \hash('sha256', $bytes);
        $byteCount ??= \random_int(128, 4194304);
        $extension ??= \array_rand(self::MIME_TYPES);
        $mediaType ??= self::MIME_TYPES[$extension];

        $filePath ??= \vsprintf('/tmp/%s.%s', [
            $bytes, $extension,
        ]);

        $fileName ??= \basename($filePath);

        if (null === $remoteKey) {
            $prefix1 = \substr($fingerprint, 0, 2);
            $prefix2 = \substr($fingerprint, 2, 2);

            $remoteKey = \vsprintf('%s/%s/%s', [
                $prefix1, $prefix2, $fileName,
            ]);
        }

        parent::__construct($fingerprint, $mediaType, $byteCount, $filePath, $fileName, $extension, $remoteKey);
    }

    public function __destruct()
    {
    }
}
