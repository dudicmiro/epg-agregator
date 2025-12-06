<?php

namespace EpgAggregator\Parse;

final class ParsedChannel
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $logoUrl,
    ) {}
}
