<?php
declare(strict_types=1);

class MongoConnection
{
    private $manager;

    public function __construct(string $uri, array $uriOptions = [], array $driverOptions = [])
    {
        $this->manager = new MongoDB\Driver\Manager($uri, $uriOptions, $driverOptions);
    }

    public function getManager(): MongoDB\Driver\Manager
    {
        return $this->manager;
    }

    public function ping(): bool
    {
        $cmd = new MongoDB\Driver\Command(['ping' => 1]);
        $this->manager->executeCommand('admin', $cmd);
        return true;
    }

    public function listDatabases(): array
    {
        $cmd = new MongoDB\Driver\Command(['listDatabases' => 1]);
        $cursor = $this->manager->executeCommand('admin', $cmd);
        $result = current($cursor->toArray());
        return (array) ($result->databases ?? []);
    }

    public function getDatabaseStats(string $db): array
    {
        return $this->runCommand($db, ['dbStats' => 1]);
    }

    public function listCollections(string $db): array
    {
        $cmd = new MongoDB\Driver\Command(['listCollections' => 1]);
        $cursor = $this->manager->executeCommand($db, $cmd);
        $collections = [];
        foreach ($cursor as $col) {
            $collections[] = (array) $col;
        }
        return $collections;
    }

    public function createCollection(string $db, string $name): void
    {
        $cmd = new MongoDB\Driver\Command(['create' => $name]);
        $this->manager->executeCommand($db, $cmd);
    }

    public function dropCollection(string $db, string $name): void
    {
        $cmd = new MongoDB\Driver\Command(['drop' => $name]);
        $this->manager->executeCommand($db, $cmd);
    }

    public function getCollectionStats(string $db, string $collection): array
    {
        return $this->runCommand($db, ['collStats' => $collection]);
    }

    public function find(string $db, string $collection, array $filter = [], array $options = []): array
    {
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $this->manager->executeQuery($db . '.' . $collection, $query);
        $docs = [];
        foreach ($cursor as $doc) {
            $docs[] = $doc;
        }
        return $docs;
    }

    public function count(string $db, string $collection, array $filter = []): int
    {
        $result = $this->runCommand($db, [
            'count' => $collection,
            'query' => (object) $filter,
        ]);
        return (int) ($result['n'] ?? 0);
    }

    public function insertOne(string $db, string $collection, array $document): MongoDB\Driver\WriteResult
    {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        return $this->manager->executeBulkWrite($db . '.' . $collection, $bulk);
    }

    public function updateOne(string $db, string $collection, array $filter, array $update): MongoDB\Driver\WriteResult
    {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $update, ['multi' => false]);
        return $this->manager->executeBulkWrite($db . '.' . $collection, $bulk);
    }

    public function replaceOne(string $db, string $collection, array $filter, array $replacement): MongoDB\Driver\WriteResult
    {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $replacement, ['multi' => false]);
        return $this->manager->executeBulkWrite($db . '.' . $collection, $bulk);
    }

    public function deleteOne(string $db, string $collection, array $filter): MongoDB\Driver\WriteResult
    {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 1]);
        return $this->manager->executeBulkWrite($db . '.' . $collection, $bulk);
    }

    public function aggregate(string $db, string $collection, array $pipeline): array
    {
        $cmd = new MongoDB\Driver\Command([
            'aggregate' => $collection,
            'pipeline'  => $pipeline,
            'cursor'    => (object) [],
        ]);
        $cursor = $this->manager->executeCommand($db, $cmd);
        $docs = [];
        foreach ($cursor as $doc) {
            $docs[] = $doc;
        }
        return $docs;
    }

    public function listIndexes(string $db, string $collection): array
    {
        $cmd = new MongoDB\Driver\Command(['listIndexes' => $collection]);
        $cursor = $this->manager->executeCommand($db, $cmd);
        $indexes = [];
        foreach ($cursor as $idx) {
            $indexes[] = (array) $idx;
        }
        return $indexes;
    }

    public function createIndex(string $db, string $collection, array $keys, array $options = []): string
    {
        $index = ['key' => $keys];
        if (!empty($options['name'])) {
            $index['name'] = $options['name'];
        }
        if (!empty($options['unique'])) {
            $index['unique'] = true;
        }
        if (!empty($options['sparse'])) {
            $index['sparse'] = true;
        }
        if (isset($options['expireAfterSeconds'])) {
            $index['expireAfterSeconds'] = (int) $options['expireAfterSeconds'];
        }

        $cmd = new MongoDB\Driver\Command([
            'createIndexes' => $collection,
            'indexes'       => [$index],
        ]);
        $result = $this->runCommand($db, [
            'createIndexes' => $collection,
            'indexes'       => [$index],
        ]);
        return $index['name'] ?? '';
    }

    public function dropIndex(string $db, string $collection, string $indexName): void
    {
        $cmd = new MongoDB\Driver\Command([
            'dropIndexes' => $collection,
            'index'       => $indexName,
        ]);
        $this->manager->executeCommand($db, $cmd);
    }

    public function serverStatus(): array
    {
        return $this->runCommand('admin', ['serverStatus' => 1]);
    }

    public function buildInfo(): array
    {
        return $this->runCommand('admin', ['buildInfo' => 1]);
    }

    public function hostInfo(): array
    {
        return $this->runCommand('admin', ['hostInfo' => 1]);
    }

    private function runCommand(string $db, array $cmdDoc): array
    {
        $cmd = new MongoDB\Driver\Command($cmdDoc);
        $cursor = $this->manager->executeCommand($db, $cmd);
        $result = current($cursor->toArray());
        return $this->toArray($result);
    }

    private function toArray($obj): array
    {
        if ($obj === null) {
            return [];
        }

        $arr = (array) $obj;
        foreach ($arr as $k => $v) {
            if (is_object($v) && !($v instanceof MongoDB\BSON\ObjectId) && !($v instanceof MongoDB\BSON\UTCDateTime)) {
                $arr[$k] = $this->toArray($v);
            }
        }
        return $arr;
    }
}
