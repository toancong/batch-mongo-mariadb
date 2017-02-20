<?php
require __DIR__ . '/vendor/autoload.php';

class MongoHelper
{
    const COLLECTIONS = ['shops', 'user_shops', 'user', 'shifts', 'time_cards', 'holidays', 'business_times', 'request_day_offs', 'breaks'];

    protected $client = null;
    protected $connectionStr;
    protected $db;

    public function __construct($connectionStr, $db)
    {
        $this->connectionStr = $connectionStr;
        $this->db = $db;
    }

    public function getClient()
    {
        if ($this->client === null) {
            $this->client = (new MongoDB\Client($this->connectionStr))->{$this->db};
        }
        return $this->client;
    }

    public function getUpdatedData($meta = [], $limit = 1000)
    {
        $results = [];
        foreach (static::COLLECTIONS as $collectionName) {
            $condition = [];
            if (isset($meta[$collectionName]['last_updated_at'])) {
                $condition['updated_at'] = ['$gte' => new MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_updated_at'])];
            }
            if (isset($meta[$collectionName]['last_updated_id'])) {
                $condition['_id'] = ['$gt' => new MongoDB\BSON\ObjectID($meta[$collectionName]['last_updated_id'])];
            }
            $collection = $this->getClient()->$collectionName;
            $items = $collection->find($condition, [
                'sort' => ['_id' => 1],
                'limit' => $limit,
            ]);
            foreach ($items as $item) {
                $results[$collectionName][] = $item;
                $meta[$collectionName] = [
                    'last_updated_at' => intval((string) $item->updated_at),
                    'last_updated_id' => (string) $item->_id,
                ];
            }
        }
        return [$results, $meta];
    }

    public function getDeletedData($meta = [], $limit = 1000)
    {
        $results = [];
        foreach (static::COLLECTIONS as $collectionName) {
            $condition = [
                'deleted_at' => [
                    '$exists' => true,
                    '$ne' => null,
                ],
            ];
            if (isset($meta[$collectionName]['last_deleted_at'])) {
                $condition['deleted_at']['$gte'] = new MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_deleted_at']);
            }
            if (isset($meta[$collectionName]['last_deleted_id'])) {
                $condition['_id'] = ['$gt' => new MongoDB\BSON\ObjectID($meta[$collectionName]['last_deleted_id'])];
            }
            $collection = $this->getClient()->$collectionName;
            $items = $collection->find($condition, [
                'sort' => ['_id' => 1],
                'limit' => $limit,
            ]);
            foreach ($items as $item) {
                $results[$collectionName][] = $item;
                $meta[$collectionName] = [
                    'last_deleted_at' => intval((string) $item->deleted_at),
                    'last_deleted_id' => (string) $item->_id,
                ];
            }
        }
        return [$results, $meta];
    }
}

class MySQLHelper
{
    protected $client = null;

    protected $connectionStr;
    protected $user;
    protected $pass;
    protected $metaTable = 'sync_meta';

    public function __construct($connectionStr, $user = null, $pass = null, $metaTable = null)
    {
        $this->connectionStr = $connectionStr;
        $this->user = $user;
        $this->pass = $pass;
        if (!empty($metaTable)) {
            $this->metaTable = $metaTable;
        }
    }

    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new PDO($this->connectionStr, $this->user, $this->pass);
        }
        return $this->client;
    }

    public function init()
    {
        $createMetaTable = "CREATE TABLE IF NOT EXISTS `{$this->metaTable}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `collection` varchar(45) NOT NULL,
            `last_updated_at` bigint(20) DEFAULT NULL,
            `last_deleted_at` bigint(20) DEFAULT NULL,
            `last_updated_id` varchar(45) DEFAULT NULL,
            `last_deleted_id` varchar(45) DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `collection_UNIQUE` (`collection`)
        ) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;";
        $this->getClient()->exec($createMetaTable);
    }

    public function getLogs()
    {
        $meta = [];
        $items = $this->getClient()->query("SELECT * FROM `{$this->metaTable}`");
        foreach ($items as $item) {
            $meta[$item['collection']] = [
                'id' => intval($item['id']),
                'last_updated_at' => isset($item['last_updated_at']) ? intval($item['last_updated_at']) : null,
                'last_deleted_at' => isset($item['last_deleted_at']) ? intval($item['last_deleted_at']) : null,
                'last_updated_id' => $item['last_updated_id'],
                'last_deleted_id' => $item['last_deleted_id'],
            ];
        }
        return $meta;
    }

    public function setLogs($meta)
    {
        $this->getClient()->beginTransaction();
        $cols = [':id', ':collection', ':last_updated_at', ':last_deleted_at', ':last_updated_id', ':last_deleted_id', ':updated_at'];
        $query = "REPLACE INTO `{$this->metaTable}` VALUES (' . implode(',', $cols) . ')";
        $queries = [];
        foreach ($meta as $collectionName => $item) {
            $vals = [
                ':id' => isset($item['id']) ? $item['id'] : 'null',
                ':collection' => "'$collectionName'",
                ':last_updated_at' => isset($item['last_updated_at']) ? $item['last_updated_at'] : 'null',
                ':last_deleted_at' => isset($item['last_deleted_at']) ? $item['last_deleted_at'] : 'null',
                ':last_updated_id' => "'{$item['last_updated_id']}'",
                ':last_deleted_id' => "'{$item['last_deleted_id']}'",
                ':updated_at' => "'" . date('Y-m-d H:i:s') . "'",
            ];
            $queries[] = str_replace($cols, $vals, $query);
        }
        $this->getClient()->exec(implode(';', $queries));
        $this->getClient()->commit();
    }
}

// Gets DB helpers
$mongoHelper = new MongoHelper('mongodb://mongo:27017', 'dbmongo');
$mySQLHelper = new MySQLHelper('mysql:host=mysql;dbname=dbmysql', 'dbuser', '123456');

// Initials MySQL
$mySQLHelper->init();

// Gets meta data
$oldMeta = $mySQLHelper->getLogs();
$newMeta = $oldMeta;

// Processes
while (true) {
    // Gets data from mongoDB
    list($updatedData, $updatedMeta) = $mongoHelper->getUpdatedData($oldMeta);
    list($deletedData, $deletedMeta) = $mongoHelper->getDeletedData($oldMeta);

    $hasNewData = false;
    if (!empty($updatedData)) {
        $hasNewData = true;
        foreach ($updatedMeta as $collectionName => $meta) {
            $newMeta[$collectionName]['last_updated_at'] = $meta['last_updated_at'];
            $newMeta[$collectionName]['last_updated_id'] = $meta['last_updated_id'];
        }
        // @TODO updates data here
    }
    if (!empty($deletedData)) {
        $hasNewData = true;
        foreach ($deletedMeta as $collectionName => $meta) {
            $newMeta[$collectionName]['last_deleted_at'] = $meta['last_deleted_at'];
            $newMeta[$collectionName]['last_deleted_id'] = $meta['last_deleted_id'];
        }
        // @TODO deletes data here
    }

    if ($hasNewData) {
        $mySQLHelper->setLogs($newMeta);
        $oldMeta = $newMeta;
        echo "Has data\n";
    }
}
