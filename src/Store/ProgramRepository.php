<?php

namespace EpgAggregator\Store;

use PDO;
use DateTimeImmutable;
use EpgAggregator\Domain\Program;

final class ProgramRepository
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function insertIgnoreDuplicate(Program $program): ?Program
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR IGNORE INTO programs
             (channel_id, title, subtitle, description, start_utc, end_utc, source, source_program_id, dedup_key)
             VALUES (:channel_id, :title, :subtitle, :description, :start_utc, :end_utc, :source, :source_program_id, :dedup_key)'
        );

        $stmt->execute([
            'channel_id'        => $program->channelId,
            'title'             => $program->title,
            'subtitle'          => $program->subtitle,
            'description'       => $program->description,
            'start_utc'         => $program->startUtc->format(DATE_ATOM),
            'end_utc'           => $program->endUtc->format(DATE_ATOM),
            'source'            => $program->source,
            'source_program_id' => $program->sourceProgramId,
            'dedup_key'         => $program->dedupKey,
        ]);

        if ($stmt->rowCount() === 0) {
            // IGNORE – duplicita
            return null;
        }

        return new Program(
            id: (int) $this->pdo->lastInsertId(),
            channelId: $program->channelId,
            title: $program->title,
            subtitle: $program->subtitle,
            description: $program->description,
            startUtc: $program->startUtc,
            endUtc: $program->endUtc,
            source: $program->source,
            sourceProgramId: $program->sourceProgramId,
            dedupKey: $program->dedupKey,
        );
    }

    /**
     * @return Program[]
     */
    public function findByChannelId(int $channelId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, channel_id, title, subtitle, description,
                    start_utc, end_utc, source, source_program_id, dedup_key
             FROM programs
             WHERE channel_id = :channel_id
             ORDER BY start_utc ASC'
        );
        $stmt->execute(['channel_id' => $channelId]);

        $programs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $start = DateTimeImmutable::createFromFormat(DATE_ATOM, $row['start_utc']);
            $end   = DateTimeImmutable::createFromFormat(DATE_ATOM, $row['end_utc']);

            $programs[] = new Program(
                id: (int) $row['id'],
                channelId: (int) $row['channel_id'],
                title: $row['title'],
                subtitle: $row['subtitle'] !== null ? (string) $row['subtitle'] : null,
                description: $row['description'] !== null ? (string) $row['description'] : null,
                startUtc: $start,
                endUtc: $end,
                source: $row['source'],
                sourceProgramId: $row['source_program_id'],
                dedupKey: $row['dedup_key'],
            );
        }

        return $programs;
    }
}
