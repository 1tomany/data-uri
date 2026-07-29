<?php

namespace OneToMany\DataUri;

use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\Contract\MediaTypeResolverInterface;
use OneToMany\DataUri\Contract\Source\SourceResolverInterface;
use OneToMany\DataUri\Contract\TemporaryFileFactoryInterface;
use OneToMany\DataUri\Contract\TemporaryFileInterface;
use OneToMany\DataUri\Exception\FileTooLargeException;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Source\SourceResolver;
use Symfony\Component\Filesystem\Filesystem;

use function base64_decode;
use function intdiv;
use function sprintf;
use function strlen;

final readonly class DataDecoder
{
    /**
     * @deprecated use TemporaryFileFactory::LIBRARY_DIRECTORY
     */
    public const string LIBRARY_DIRECTORY = TemporaryFileFactory::LIBRARY_DIRECTORY;

    private SourceResolverInterface $sourceResolver;

    private TemporaryFileFactoryInterface $temporaryFileFactory;

    public function __construct(
        Filesystem $filesystem = new Filesystem(),
        ?string $temporaryDirectory = null,
        int $maximumBytes = TemporaryFileFactory::DEFAULT_MAXIMUM_BYTES,
        ?SourceResolverInterface $sourceResolver = null,
        ?MediaTypeResolverInterface $mediaTypeResolver = null,
        ?TemporaryFileFactoryInterface $temporaryFileFactory = null,
    ) {
        $this->sourceResolver = $sourceResolver ?? new SourceResolver();
        $this->temporaryFileFactory = $temporaryFileFactory ?? new TemporaryFileFactory(
            $filesystem,
            $mediaTypeResolver ?? new MediaTypeResolver(),
            $temporaryDirectory,
            $maximumBytes,
        );
    }

    public function decode(
        string|\Stringable $data,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
    ): TemporaryFileInterface {
        $source = $this->sourceResolver->resolve($data);
        $openedSource = $source->open();

        try {
            return $this->temporaryFileFactory->createFromStream(
                $openedSource->getStream(),
                $type,
                $name ?? $source->suggestedName,
                $openedSource->declaredMediaType,
            );
        } finally {
            $openedSource->close();
        }
    }

    public function decodeBase64(
        string $data,
        string|Type|MediaType $type,
        ?string $name = null,
    ): TemporaryFileInterface {
        $mediaType = MediaType::create($type);
        $maximumEncodedLength = 4 * intdiv($this->temporaryFileFactory->getMaximumBytes() + 2, 3);

        if (strlen($data) > $maximumEncodedLength) {
            throw new FileTooLargeException(sprintf('The decoded temporary file may exceed the maximum size of %d bytes.', $this->temporaryFileFactory->getMaximumBytes()));
        }

        if (false === $contents = base64_decode($data, true)) {
            throw new InvalidArgumentException('The data is not valid Base64.');
        }

        return $this->temporaryFileFactory->createFromString($contents, $mediaType, $name);
    }

    /**
     * @param resource $stream
     */
    public function decodeStream(
        mixed $stream,
        string|Type|MediaType|null $type = null,
        ?string $name = null,
        ?string $declaredType = null,
    ): TemporaryFileInterface {
        return $this->temporaryFileFactory->createFromStream($stream, $type, $name, $declaredType);
    }

    public function decodeText(
        string $text,
        string|Type|MediaType $type = Type::Txt,
        ?string $name = null,
    ): TemporaryFileInterface {
        $mediaType = MediaType::create($type);

        if (!$mediaType->isText()) {
            throw new InvalidArgumentException(sprintf('The media type "%s" is not text.', $mediaType->value));
        }

        return $this->temporaryFileFactory->createFromString($text, $mediaType, $name);
    }
}
