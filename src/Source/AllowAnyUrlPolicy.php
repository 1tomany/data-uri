<?php

namespace OneToMany\DataUri\Source;

use OneToMany\DataUri\Contract\Source\UrlPolicyInterface;

final readonly class AllowAnyUrlPolicy implements UrlPolicyInterface
{
    public function assertAllowed(string $url): void
    {
    }
}
