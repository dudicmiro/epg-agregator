<?php

namespace EpgAggregator\Export;

use XMLWriter;
use DateTimeImmutable;
use EpgAggregator\Store\ChannelRepository;
use EpgAggregator\Store\ProgramRepository;

final class XmltvExporter
{
    public function __construct(
        private ChannelRepository $channelRepo,
        private ProgramRepository $programRepo,
    ) {}

    public function export(string $filePath): void
    {
        $channels = $this->channelRepo->findAll();

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('tv');
        $writer->writeAttribute('generator-info-name', 'epg-aggregator');

        // channels
        foreach ($channels as $ch) {
            $writer->startElement('channel');
            $writer->writeAttribute('id', $ch->xmltvId);

            $writer->startElement('display-name');
            $writer->text($ch->name);
            $writer->endElement(); // display-name

            if ($ch->logoUrl !== null) {
                $writer->startElement('icon');
                $writer->writeAttribute('src', $ch->logoUrl);
                $writer->endElement(); // icon
            }

            $writer->endElement(); // channel
        }

        // programmes
        foreach ($channels as $ch) {
            $programs = $this->programRepo->findByChannelId($ch->id);

            foreach ($programs as $p) {
                $writer->startElement('programme');
                $writer->writeAttribute('channel', $ch->xmltvId);
                $writer->writeAttribute('start', $this->formatXmltvTime($p->startUtc));
                $writer->writeAttribute('stop', $this->formatXmltvTime($p->endUtc));

                $writer->startElement('title');
                $writer->text($p->title);
                $writer->endElement(); // title

                if ($p->subtitle !== null && $p->subtitle !== '') {
                    $writer->startElement('sub-title');
                    $writer->text($p->subtitle);
                    $writer->endElement(); // sub-title
                }

                if ($p->description !== null && $p->description !== '') {
                    $writer->startElement('desc');
                    $writer->text($p->description);
                    $writer->endElement(); // desc
                }

                $writer->endElement(); // programme
            }
        }

        $writer->endElement(); // tv
        $writer->endDocument();

        $xml = $writer->outputMemory();

        // .gz podpora podľa prípony
        if (str_ends_with($filePath, '.gz')) {
            $gz = gzencode($xml, 9);
            if ($gz === false) {
                throw new \RuntimeException('Failed to gzencode XMLTV output');
            }
            file_put_contents($filePath, $gz);
        } else {
            file_put_contents($filePath, $xml);
        }
    }

    private function formatXmltvTime(DateTimeImmutable $dt): string
    {
        // XMLTV: 20251212060000 +0000
        return $dt->format('YmdHis') . ' +0000';
    }
}
