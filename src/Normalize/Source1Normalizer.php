<?php

namespace EpgAggregator\Normalize;

use DateTimeImmutable;
use DateTimeZone;
use EpgAggregator\Domain\Channel;
use EpgAggregator\Parse\ParsedEpg;

final class Source1Normalizer implements NormalizerInterface
{
    public function normalize(ParsedEpg $parsed, string $source): NormalizedResult
    {
        $channels = [];
        foreach ($parsed->channels as $ch) {
            $channels[] = new Channel(
                id: null,
                xmltvId: $ch->id,
                name: $ch->name,
                logoUrl: $ch->logoUrl,
            );
        }

        $programs = [];
        foreach ($parsed->programs as $p) {
            $startUtc = $this->toUtc($p->startRaw);
            $endUtc   = $this->toUtc($p->endRaw);

            $dedupKey = $this->makeDedupKey(
                $p->channelId,
                $startUtc,
                $endUtc,
                $p->title,
                $source,
                $p->sourceProgramId,
            );

            $programs[] = new NormalizedProgram(
                channelXmltvId: $p->channelId,
                title: $p->title,
                subtitle: $p->subtitle,
                description: $p->description,
                startUtc: $startUtc,
                endUtc: $endUtc,
                source: $source,
                sourceProgramId: $p->sourceProgramId,
                dedupKey: $dedupKey,
            );
        }

        return new NormalizedResult($channels, $programs);
    }

    private function toUtc(string $raw): DateTimeImmutable
    {
        // XMLTV štandard: 20251206120000 +0100
        if (preg_match('/^\d{14} [\+\-]\d{4}$/', $raw)) {
            $dt = DateTimeImmutable::createFromFormat('YmdHis O', $raw);
            if ($dt === false) {
                throw new \RuntimeException("Cannot parse datetime '{$raw}'");
            }
            return $dt->setTimezone(new DateTimeZone('UTC'));
        }

        // fallback – bez offsetu, berieme ako lokálny čas Europe/Bratislava
        $tz = new DateTimeZone('Europe/Bratislava');
        $dt = DateTimeImmutable::createFromFormat('YmdHis', $raw, $tz);
        if ($dt === false) {
            throw new \RuntimeException("Cannot parse datetime '{$raw}'");
        }
        return $dt->setTimezone(new DateTimeZone('UTC'));
    }

    private function makeDedupKey(
        string $xmltvId,
        DateTimeImmutable $startUtc,
        DateTimeImmutable $endUtc,
        string $title,
        string $source,
        string $sourceProgramId,
    ): string {
        return sha1(
            $xmltvId . '|' .
            $startUtc->format(DATE_ATOM) . '|' .
            $endUtc->format(DATE_ATOM) . '|' .
            mb_strtolower($title, 'UTF-8') . '|' .
            $source . '|' .
            $sourceProgramId
        );
    }
}
