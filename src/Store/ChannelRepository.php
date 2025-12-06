<?php

namespace EpgAggregator\Store;

use EpgAggregator\Domain\Channel;
use PDO;

final class ChannelRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function upsert(Channel $channel): Channel
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO channels (xmltv_id, name, logo_url)
             VALUES (:xmltv_id, :name, :logo_url)
             ON CONFLICT(xmltv_id) DO UPDATE SET
               name = excluded.name,
               logo_url = excluded.logo_url'
        );

        $stmt->execute([
            ':xmltv_id' => $channel->xmltvId,
            ':name'     => $channel->name,
            ':logo_url' => $channel->logoUrl,
        ]);

        $id = (int)$this->pdo
            ->query('SELECT id FROM channels WHERE xmltv_id = ' . $this->pdo->quote($channel->xmltvId))
            ->fetchColumn();

        return new Channel(
            $id,
            $channel->xmltvId,
            $channel->name,
            $channel->logoUrl
        );
    }

    /** @return Channel[] */
    public function findAll(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM channels ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn (array $row) => new Channel(
                (int)$row['id'],
                $row['xmltv_id'],
                $row['name'],
                $row['logo_url'] ?? null
            ),
            $rows
        );
    }
}
