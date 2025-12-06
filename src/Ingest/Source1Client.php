<?php

namespace EpgAggregator\Ingest;

final class Source1Client
{
    public function __construct(
        private string $pathOrUrl,
    ) {}

    public function fetch(): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
            ],
        ]);

        $raw = @file_get_contents($this->pathOrUrl, false, $context);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read source: {$this->pathOrUrl}");
        }

        // Ak je to gzip (.gz alebo HTTP gzip), rozbalíme
        if (substr($raw, 0, 2) === "\x1f\x8b") {
            $decoded = @gzdecode($raw);
            if ($decoded === false) {
                throw new \RuntimeException("Cannot gzdecode source: {$this->pathOrUrl}");
            }
            return $decoded;
        }

        return $raw;
    }
}
