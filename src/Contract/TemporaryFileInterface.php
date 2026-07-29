<?php

namespace OneToMany\DataUri\Contract;

use OneToMany\DataUri\Exception\RuntimeException;

interface TemporaryFileInterface extends EncodableFileInterface, HashableFileInterface
{
    public function hasSameContentAs(self $file): bool;

    public function refersToSameFileAs(self $file): bool;

    /**
     * @deprecated use hasSameContentAs() or refersToSameFileAs()
     */
    public function equals(self $file, bool $strict = false): bool;

    /**
     * Deletes the owned temporary file. Calling this more than once is safe.
     *
     * @throws RuntimeException when deterministic cleanup fails
     */
    public function delete(): void;

    /**
     * Transfers cleanup responsibility to the caller and returns the path.
     *
     * @return non-empty-string
     */
    public function detach(): string;

    public function isManaged(): bool;
}
