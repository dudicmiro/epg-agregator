<?php

namespace EpgAggregator\Export;

use EpgAggregator\Store\ChannelRepository;
use EpgAggregator\Domain\Channel;

final class ChannelsJsonExporter
{
    public function __construct(
        private ChannelRepository $channelRepo,
    ) {}

    public function export(string $path): void
    {
        /** @var Channel[] $channels */
        $channels = $this->channelRepo->findAllMaster();

        $out = [];
        foreach ($channels as $ch) {
            $basealias = $this->slug($ch->name);

            $aliases = [];
            if ($basealias !== '') {
                $aliases[] = $basealias;
            }
            if ($ch->xmltvId !== '') {
                $aliases[] = $ch->xmltvId;
            }

            // unikátne aliasy
            $aliases = array_values(array_unique($aliases));

            // hlsproxy štýl: |alias1|alias2|
            $aliasesString = '|' . implode('|', $aliases) . '|';

            $out[] = [
                '_id'       => $ch->id,
                'aliases'   => $aliasesString,
                'basealias' => $basealias !== '' ? $basealias : $ch->xmltvId,
                'country'   => null,          // neskôr doplníme podľa potreby
                'logo_url'  => $ch->logoUrl,
                'name'      => $ch->name,
                'group'     => 'custom',      // placeholder, dá sa rozšíriť
            ];
        }

        $json = json_encode(
            $out,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new \RuntimeException('Failed to encode channels JSON.');
        }

        if (@file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Failed to write channels JSON to {$path}");
        }

        echo "Channels JSON exported to {$path}\n";
    }

    private function slug(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = mb_strtolower($name, 'UTF-8');

        // pokus o odstránenie diakritiky
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($normalized !== false) {
            $name = $normalized;
        }

        // necháme len a-z0-9
        $name = preg_replace('/[^a-z0-9]+/', '', $name);
        return $name ?? '';
    }
}
