<?php

namespace OneToMany\DataUri\Source;

use OneToMany\DataUri\Exception\RuntimeException;

use function fclose;
use function is_resource;

final class OpenedSource
{
    /**
     * @var resource|null
     */
    private mixed $stream;

    /**
     * @param resource $stream
     */
    public function __construct(
        mixed $stream,
        public readonly ?string $declaredMediaType = null,
    ) {
        if (!is_resource($stream)) {
            throw new RuntimeException('The opened source must contain a stream resource.');
        }

        $this->stream = $stream;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('The source stream is already closed.');
        }

        return $this->stream;
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = null;
    }
}
