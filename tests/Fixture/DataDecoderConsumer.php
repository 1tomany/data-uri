<?php

namespace OneToMany\DataUri\Tests\Fixture;

use OneToMany\DataUri\DataDecoder;

final readonly class DataDecoderConsumer
{
    public function __construct(
        public DataDecoder $dataDecoder,
    ) {
    }
}
