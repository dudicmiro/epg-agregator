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
        // nájdi podľa xmltv_id
        $stmt = $this->pdo->prepare(
            'SELECT id, xmltv_id, name, logo_url FROM channels WHERE xmltv_id = :xmltv_id'
        );
        $stmt->execute(['xmltv_id' => $channel->xmltvId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // update len name/logo, nemeniť is_master/position
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

        // insert – nový channel je default discovered (is_master=0)
        $insert = $this->pdo->prepare(
            'INSERT INTO channels (xmltv_id, name, logo_url)
             VALUES (:xmltv_id, :name, :logo_url)'
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
     * Všetky kanály (bez ohľadu na master flag) – na interné použitie.
     *
     * @return Channel[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, xmltv_id, name, logo_url
             FROM channels
             ORDER BY id ASC'
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

    /**
     * Master kanály v poradí (position, potom name).
     *
     * @return Channel[]
     */
    public function findAllMaster(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, xmltv_id, name, logo_url
             FROM channels
             WHERE is_master = 1
             ORDER BY position ASC, name ASC'
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

    /**
     * Naplní master zoznam podľa mena (textarea lines).
     * Každý riadok = jeden kanál v poradí (1,2,3,...).
     *
     * @param string[] $namesOrdered
     */
    public function updateMasterFromList(array $namesOrdered): void
    {
        $this->pdo->beginTransaction();

        try {
            // reset všetkých
            $this->pdo->exec('UPDATE channels SET is_master = 0, position = NULL');

            $select = $this->pdo->prepare(
                'SELECT id FROM channels WHERE name = :name LIMIT 1'
            );
            $update = $this->pdo->prepare(
                'UPDATE channels SET is_master = 1, position = :position WHERE id = :id'
            );
            $insert = $this->pdo->prepare(
                'INSERT INTO channels (xmltv_id, name, logo_url, is_master, position)
                 VALUES (:xmltv_id, :name, :logo_url, 1, :position)'
            );

            $position = 1;
            foreach ($namesOrdered as $name) {
                if ($name === '') {
                    continue;
                }

                $select->execute(['name' => $name]);
                $row = $select->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $update->execute([
                        'position' => $position,
                        'id'       => $row['id'],
                    ]);
                } else {
                    // nový kanál, zatiaľ xmltv_id = name (neskôr ho vieš doladiť aliasmi)
                    $insert->execute([
                        'xmltv_id' => $name,
                        'name'     => $name,
                        'logo_url' => null,
                        'position' => $position,
                    ]);
                }

                $position++;
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
