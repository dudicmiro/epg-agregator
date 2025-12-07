<?php

namespace EpgAggregator\Store;

use PDO;

final class SchemaManager
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function ensureSchema(): void
    {
        // channels
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS channels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                xmltv_id TEXT NOT NULL,
                name TEXT NOT NULL,
                logo_url TEXT,
                is_master INTEGER NOT NULL DEFAULT 0,
                position INTEGER
            )'
        );
        $this->pdo->exec(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_channels_xmltv_id
             ON channels (xmltv_id)'
        );

        // programs
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS programs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                subtitle TEXT,
                description TEXT,
                start_utc TEXT NOT NULL,
                end_utc TEXT NOT NULL,
                source TEXT NOT NULL,
                source_program_id TEXT NOT NULL,
                dedup_key TEXT NOT NULL,
                FOREIGN KEY (channel_id) REFERENCES channels(id)
            )'
        );
        $this->pdo->exec(
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_programs_dedup
             ON programs (dedup_key)'
        );
        $this->pdo->exec(
            'CREATE INDEX IF NOT EXISTS idx_programs_channel_start
             ON programs (channel_id, start_utc)'
        );

        // settings (kv store)
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );
    }
}
