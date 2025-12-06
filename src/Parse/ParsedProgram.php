<?php

namespace EpgAggregator\Parse;

final class ParsedProgram
{
    public function __construct(
        public string $sourceProgramId,
        public string $channelId,
        public string $title,
        public ?string $subtitle,
        public ?string $description,
        public string $startRaw,
        public string $endRaw,
    ) {}
}
