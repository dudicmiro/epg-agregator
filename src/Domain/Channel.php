<?php

namespace EpgAggregator\Domain;

final class Channel
{
    public function __construct(
        public ?int $id,
        public string $xmltvId,
        public string $name,
        public ?string $logoUrl = null
    ) {}
}
