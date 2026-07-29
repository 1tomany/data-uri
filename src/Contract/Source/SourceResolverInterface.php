<?php

namespace OneToMany\DataUri\Contract\Source;

use OneToMany\DataUri\Source\ResolvedSource;

interface SourceResolverInterface
{
    public function resolve(string|\Stringable $data): ResolvedSource;
}
