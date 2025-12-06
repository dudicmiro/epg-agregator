<?php

namespace EpgAggregator\Parse;

interface ParserInterface
{
    public function parse(string $raw): ParsedEpg;
}
