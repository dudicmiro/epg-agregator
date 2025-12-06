<?php

namespace EpgAggregator\Domain;

use DateTimeImmutable;

final class Program
{
    public function __construct(
        public ?int $id,
        public int $channelId,
        public string $title,
        public ?string $subtitle,
        public ?string $description,
        public DateTimeImmutable $startUtc,
        public DateTimeImmutable $endUtc,
        public ?string $source,
        public ?string $sourceProgramId,
        public string $dedupKey
    ) {}
}
