<?php

namespace OneToMany\DataUri\Contract\Source;

interface UrlPolicyInterface
{
    /**
     * @throws \OneToMany\DataUri\Exception\InvalidArgumentException when the URL is disallowed
     */
    public function assertAllowed(string $url): void;
}
