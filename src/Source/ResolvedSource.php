<?php

namespace OneToMany\DataUri\Source;

final readonly class ResolvedSource
{
    /**
     * @param \Closure(): OpenedSource $opener
     * @param ?non-empty-string $suggestedName
     */
    public function __construct(
        public string $description,
        public ?string $suggestedName,
        private \Closure $opener,
    ) {
    }

    public function open(): OpenedSource
    {
        return ($this->opener)();
    }
}
