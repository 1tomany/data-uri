<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\MediaTypeResolverInterface;

use function mime_content_type;

final readonly class MediaTypeResolver implements MediaTypeResolverInterface
{
    public function resolve(
        string $path,
        string|Type|MediaType|null $preferred = null,
        ?string $declared = null,
    ): MediaType {
        if (null !== $preferred) {
            return MediaType::create($preferred);
        }

        if (null !== $declared) {
            return MediaType::fromString($declared);
        }

        return MediaType::fromString(@mime_content_type($path) ?: Type::Other->getFormat());
    }
}
