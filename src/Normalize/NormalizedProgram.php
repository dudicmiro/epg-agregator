<?php

namespace EpgAggregator\Normalize;

use DateTimeImmutable;

final class NormalizedProgram
{
    public function __construct(
        public string $channelXmltvId,
        public string $title,
        public ?string $subtitle,
        public ?string $description,
        public DateTimeImmutable $startUtc,
        public DateTimeImmutable $endUtc,
        public string $source,
        public string $sourceProgramId,
        public string $dedupKey,
    ) {}
}
