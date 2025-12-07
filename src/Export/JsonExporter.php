<?php

namespace EpgAggregator\Export;

use EpgAggregator\Store\ChannelRepository;
use EpgAggregator\Store\ProgramRepository;

final class JsonExporter
{
    public function __construct(
        private ChannelRepository $channelRepo,
        private ProgramRepository $programRepo,
    ) {}

    public function export(string $filePath): void
    {
        $channels = $this->channelRepo->findAllMaster();

        $out = [
            'generated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'channels' => [],
        ];

        foreach ($channels as $ch) {
            $programs = $this->programRepo->findByChannelId($ch->id);

            $programItems = [];
            foreach ($programs as $p) {
                $programItems[] = [
                    'start_utc' => $p->startUtc->format(DATE_ATOM),
                    'end_utc'   => $p->endUtc->format(DATE_ATOM),
                    'title'     => $p->title,
                    'subtitle'  => $p->subtitle,
                    'description' => $p->description,
                    'source'    => $p->source,
                    'source_program_id' => $p->sourceProgramId,
                ];
            }

            $out['channels'][] = [
                'id'       => $ch->id,
                'xmltv_id' => $ch->xmltvId,
                'name'     => $ch->name,
                'logo_url' => $ch->logoUrl,
                'programs' => $programItems,
            ];
        }

        $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        file_put_contents($filePath, $json);
    }
}
