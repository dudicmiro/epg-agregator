<?php

namespace EpgAggregator\Normalize;

use EpgAggregator\Domain\Channel;

final class NormalizedResult
{
    /**
     * @param Channel[]           $channels
     * @param NormalizedProgram[] $programs
     */
    public function __construct(
        public array $channels,
        public array $programs,
    ) {}
}
