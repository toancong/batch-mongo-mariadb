<?php
namespace BatchMongoRDB\Core;

class RDBHelper
{
    protected $client = null;

    protected $connectionStr;
    protected $user;
    protected $pass;
    protected $metaTable = 'sync_meta';
    protected static $config = [];

    public static function getConfig()
    {
        if (empty(static::$config)) {
            static::$config = [
                'host' => getenv('RDB_HOST'),
                'port' => getenv('RDB_PORT'),
                'database' => getenv('RDB_DB'),
                'username' => getenv('RDB_USER'),
                'password' => getenv('RDB_PASS'),
            ];
        }
        return static::$config;
    }

    public function __construct($config = [])
    {
        $config = array_merge(static::getConfig(), $config);
        $temp = [];
        $temp[] = empty($config['host']) ? 'host=127.0.0.1' : "host={$config['host']}";
        $temp[] = empty($config['port']) ? 'port=3306' : "port={$config['port']}";
        $temp[] = empty($config['database']) ? 'dbname=db' : "dbname={$config['database']}";
        $this->connectionStr = 'mysql:' . implode(';', $temp);
        if (!empty($config['username'])) {
            $this->user = $config['username'];
        }
        if (!empty($config['password'])) {
            $this->pass = $config['password'];
        }
        if (!empty($config['metatable'])) {
            $this->metaTable = $config['metatable'];
        }
    }

    public function getClient()
    {
        if ($this->client === null) {
            $this->client = new \PDO($this->connectionStr, $this->user, $this->pass);
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

    public function getMeta()
    {
        $meta = [];
        $items = $this->getClient()->query("SELECT * FROM `{$this->metaTable}`");

        if (!$items) {
            return $meta;
        }

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

    public function setMeta($meta)
    {
        $cols = [':id', ':collection', ':last_updated_at', ':last_deleted_at', ':last_updated_id', ':last_deleted_id', ':updated_at'];
        $query = "REPLACE INTO `{$this->metaTable}` VALUES (" . implode(',', $cols) . ')';
        $queries = [];
        foreach ($meta as $collectionName => $item) {
            $vals = [
                ':id' => isset($item['id']) ? $item['id'] : 'null',
                ':collection' => "'$collectionName'",
                ':last_updated_at' => isset($item['last_updated_at']) ? $item['last_updated_at'] : 'null',
                ':last_deleted_at' => isset($item['last_deleted_at']) ? $item['last_deleted_at'] : 'null',
                ':last_updated_id' => isset($item['last_updated_id']) ? "'{$item['last_updated_id']}'" : 'null',
                ':last_deleted_id' => isset($item['last_deleted_id']) ? "'{$item['last_deleted_id']}'" : 'null',
                ':updated_at' => "'" . date('Y-m-d H:i:s') . "'",
            ];
            $queries[] = str_replace($cols, $vals, $query);
        }
        $this->getClient()->exec(implode(';', $queries));
    }
}
