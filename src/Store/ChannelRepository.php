<?php

namespace EpgAggregator\Store;

use PDO;
use EpgAggregator\Domain\Channel;

final class ChannelRepository
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function upsert(Channel $channel): Channel
    {
        // najprv skúsime nájsť existujúci kanál podľa xmltv_id
        $stmt = $this->pdo->prepare(
            'SELECT id, xmltv_id, name, logo_url FROM channels WHERE xmltv_id = :xmltv_id'
        );
        $stmt->execute(['xmltv_id' => $channel->xmltvId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // updatneme meno/logo, ak sa zmenili
            $update = $this->pdo->prepare(
                'UPDATE channels SET name = :name, logo_url = :logo_url WHERE id = :id'
            );
            $update->execute([
                'name'     => $channel->name,
                'logo_url' => $channel->logoUrl,
                'id'       => $row['id'],
            ]);

            return new Channel(
                id: (int) $row['id'],
                xmltvId: $row['xmltv_id'],
                name: $channel->name,
                logoUrl: $channel->logoUrl,
            );
        }

        // insert
        $insert = $this->pdo->prepare(
            'INSERT INTO channels (xmltv_id, name, logo_url) VALUES (:xmltv_id, :name, :logo_url)'
        );
        $insert->execute([
            'xmltv_id' => $channel->xmltvId,
            'name'     => $channel->name,
            'logo_url' => $channel->logoUrl,
        ]);

        return new Channel(
            id: (int) $this->pdo->lastInsertId(),
            xmltvId: $channel->xmltvId,
            name: $channel->name,
            logoUrl: $channel->logoUrl,
        );
    }

    /**
     * @return Channel[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, xmltv_id, name, logo_url FROM channels ORDER BY id ASC'
        );

        $channels = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $channels[] = new Channel(
                id: (int) $row['id'],
                xmltvId: $row['xmltv_id'],
                name: $row['name'],
                logoUrl: $row['logo_url'],
            );
        }

        return $channels;
    }
}
