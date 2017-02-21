<?php
namespace BatchMongoRDB\Core;

class RDBHelper
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
