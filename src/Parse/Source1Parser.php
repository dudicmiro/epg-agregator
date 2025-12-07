<?php

namespace EpgAggregator\Parse;

use SimpleXMLElement;

final class Source1Parser implements ParserInterface
{
    public function parse(string $raw): ParsedEpg
    {
        // Povoliť veľké XML + chyby držať interne
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string(
            $raw,
            SimpleXMLElement::class,
            LIBXML_PARSEHUGE
        );

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $message = 'Failed to parse XMLTV.';
            if (!empty($errors)) {
                $first = $errors[0];
                $message .= sprintf(
                    ' Line %d, column %d: %s',
                    $first->line,
                    $first->column,
                    trim($first->message)
                );
            }

            throw new \RuntimeException($message);
        }

        // --- channels ---

        $channels = [];
        foreach ($xml->channel as $channelEl) {
            $xmltvId = (string) $channelEl['id'];

            $name = '';
            if (isset($channelEl->{'display-name'})) {
                $name = (string) $channelEl->{'display-name'}[0];
            }

            $logoUrl = null;
            if (isset($channelEl->icon['src'])) {
                $logoUrl = (string) $channelEl->icon['src'];
            }

            if ($xmltvId === '' && $name === '') {
                continue;
            }

            // POZÍCIOVÉ argumenty – nezávislé od názvov parametrov v ParsedChannel
            $channels[] = new ParsedChannel($xmltvId, $name, $logoUrl);
        }

        // --- programmes ---

        $programs = [];
        foreach ($xml->programme as $progEl) {
            $channelId = (string) $progEl['channel'];
            if ($channelId === '') {
                continue;
            }

            $start = (string) $progEl['start'];
            $stop  = (string) $progEl['stop'];

            $title = isset($progEl->title) ? (string) $progEl->title : '';
            $subtitle = isset($progEl->{'sub-title'}) ? (string) $progEl->{'sub-title'} : null;
            $description = isset($progEl->desc) ? (string) $progEl->desc : null;

            $programId = sha1($channelId . '|' . $start . '|' . $stop . '|' . $title);

            // opäť pozíciové argumenty
            $programs[] = new ParsedProgram(
                $programId,
                $channelId,
                $title,
                $subtitle,
                $description,
                $start,
                $stop,
            );
        }

        return new ParsedEpg($channels, $programs);
    }
}
