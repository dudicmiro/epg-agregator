<?php

namespace EpgAggregator\Parse;

use SimpleXMLElement;

final class Source1Parser implements ParserInterface
{
    public function parse(string $raw): ParsedEpg
    {
        // Povoliť veľké XML + chyby držať interne
        libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($raw, LIBXML_PARSEHUGE);
        } catch (\Throwable $e) {
            $errors = libxml_get_errors();
            $msg = $errors ? trim($errors[0]->message) : $e->getMessage();

            libxml_clear_errors();
            libxml_use_internal_errors(false);

            throw new \RuntimeException('Failed to parse XMLTV: ' . $msg, 0, $e);
        }

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $channels = [];
        foreach ($xml->channel as $ch) {
            $id   = (string) $ch['id'];
            $name = (string) ($ch->{'display-name'} ?? $id);
            $logo = isset($ch->icon['src']) ? (string) $ch->icon['src'] : null;

            $channels[] = new ParsedChannel(
                id: $id,
                name: $name,
                logoUrl: $logo,
            );
        }

        $programs = [];
        foreach ($xml->programme as $p) {
            $channelId   = (string) $p['channel'];
            $start       = (string) $p['start'];
            $stop        = (string) $p['stop'];
            $title       = (string) $p->title;
            $subtitle    = isset($p->{'sub-title'}) ? (string) $p->{'sub-title'} : null;
            $description = isset($p->desc) ? (string) $p->desc : null;

            $programId = sha1($channelId . '|' . $start . '|' . $stop . '|' . $title);

            $programs[] = new ParsedProgram(
                sourceProgramId: $programId,
                channelId: $channelId,
                title: $title,
                subtitle: $subtitle,
                description: $description,
                startRaw: $start,
                endRaw: $stop,
            );
        }

        return new ParsedEpg($channels, $programs);
    }
}
