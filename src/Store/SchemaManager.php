<?php

namespace EpgAggregator\Store;

use PDO;

final class SchemaManager
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function ensureSchema(): void
    {
        $this->pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS channels (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    xmltv_id  TEXT NOT NULL UNIQUE,
    name      TEXT NOT NULL,
    logo_url  TEXT
);
SQL);

        $this->pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS programs (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    channel_id        INTEGER NOT NULL,
    title             TEXT NOT NULL,
    subtitle          TEXT,
    description       TEXT,
    start_utc         TEXT NOT NULL, -- ISO8601 UTC
    end_utc           TEXT NOT NULL, -- ISO8601 UTC
    source            TEXT,
    source_program_id TEXT,
    dedup_key         TEXT NOT NULL,
    FOREIGN KEY(channel_id) REFERENCES channels(id),
    UNIQUE(dedup_key)
);
SQL);

        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS idx_programs_channel_start ON programs (channel_id, start_utc)'
        );
    }
}
