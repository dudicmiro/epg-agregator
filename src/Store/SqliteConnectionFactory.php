<?php

namespace EpgAggregator\Store;

use PDO;

final class SqliteConnectionFactory
{
    public function __construct(
        private string $pathToDb
    ) {}

    public function create(): PDO
    {
        $pdo = new PDO('sqlite:' . $this->pathToDb);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
