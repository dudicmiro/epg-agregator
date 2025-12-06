<?php

namespace EpgAggregator\Normalize;

use EpgAggregator\Parse\ParsedEpg;

interface NormalizerInterface
{
    public function normalize(ParsedEpg $parsed, string $source): NormalizedResult;
}
