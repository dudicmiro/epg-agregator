<?php

namespace EpgAggregator\Store;

use EpgAggregator\Domain\Program;
use DateTimeImmutable;
use PDO;

final class ProgramRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Insert s deduplikáciou cez UNIQUE(dedup_key).
     * Vracia Program s prideleným id, alebo null ak to bola duplicitná položka.
     */
    public function insertIgnoreDuplicate(Program $program): ?Program
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR IGNORE INTO programs
             (channel_id, title, subtitle, description, start_utc, end_utc, source, source_program_id, dedup_key)
             VALUES
             (:channel_id, :title, :subtitle, :description, :start_utc, :end_utc, :source, :source_program_id, :dedup_key)'
        );

        $stmt->execute([
            ':channel_id'        => $program->channelId,
            ':title'             => $program->title,
            ':subtitle'          => $program->subtitle,
            ':description'       => $program->description,
            ':start_utc'         => $program->startUtc->format(DATE_ATOM),
            ':end_utc'           => $program->endUtc->format(DATE_ATOM),
            ':source'            => $program->source,
            ':source_program_id' => $program->sourceProgramId,
            ':dedup_key'         => $program->dedupKey,
        ]);

        if ($this->pdo->lastInsertId() === '0') {
            // dedup_key už existuje, záznam sme preskočili
            return null;
        }

        $id = (int)$this->pdo->lastInsertId();

        return new Program(
            $id,
            $program->channelId,
            $program->title,
            $program->subtitle,
            $program->description,
            $program->startUtc,
            $program->endUtc,
            $program->source,
            $program->sourceProgramId,
            $program->dedupKey
        );
    }

    /** @return Program[] */
    public function findAllOrdered(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM programs ORDER BY start_utc')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn (array $row) => new Program(
                (int)$row['id'],
                (int)$row['channel_id'],
                $row['title'],
                $row['subtitle'] ?? null,
                $row['description'] ?? null,
                new DateTimeImmutable($row['start_utc']),
                new DateTimeImmutable($row['end_utc']),
                $row['source'] ?? null,
                $row['source_program_id'] ?? null,
                $row['dedup_key']
            ),
            $rows
        );
    }
}
