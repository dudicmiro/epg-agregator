<?php

namespace EpgAggregator\Parse;

final class ParsedEpg
{
    /**
     * @param ParsedChannel[] $channels
     * @param ParsedProgram[] $programs
     */
    public function __construct(
        public array $channels,
        public array $programs,
    ) {}
}
