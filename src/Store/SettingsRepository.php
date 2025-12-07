<?php

namespace EpgAggregator\Store;

use PDO;

final class SettingsRepository
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function get(string $key): ?string
    {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (key, value)
             VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([
            'key'   => $key,
            'value' => $value,
        ]);
    }
}
