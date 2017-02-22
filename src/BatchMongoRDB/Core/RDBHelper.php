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

    public function connect($forceNew = false)
    {
        if ($forceNew || $this->client === null) {
            $this->client = new \PDO($this->connectionStr, $this->user, $this->pass);
        }
        return true;
    }

    public function close()
    {
        if ($this->client !== null) {
            $this->client = null;
        }
        return true;
    }

    public function reconnect()
    {
        if ($this->close()) {
            return $this->connect(true);
        }
        return false;
    }

    public function getClient()
    {
        $this->connect();
        return $this->client;
    }

    public function bulk($queries = [])
    {
        if (!$queries) {
            return true;
        }

        $sps = strpos($queries[0], 'VALUES');
        $query = substr($queries[0], 0, $sps);
        $values = [];

        foreach ($queries as $value) {
            $values[] = substr($value, $sps + 6);
        }

        $query .= ' VALUES ' . implode(',', $values);
        $this->getClient()->exec($query);
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
        $this->bulk($queries);
    }

    public function deleteByIds($arr = [], $soft = true, $deletedAtByIds = [])
    {
        $query = $soft ? 'UPDATE `:table` SET `deleted_at`=:deleted_at WHERE `id` IN (:ids)' : 'DELETE FROM `:table` WHERE `id` IN (:ids)';
        $queries = [];
        foreach ($arr as $table => $ids) {
            $vals = [
                ':table' => $table,
                ':ids' => "'" . implode("','", $ids) . "'",
            ];
            if ($soft) {
                $deletedAt = null;
                if (!empty($deletedByIds)) {
                    foreach ($ids as $id) {
                        if (isset($deletedByIds[$id])) {
                            $deletedAt = "'" . $deletedByIds[$id] . "'";
                            break;
                        }
                    }
                }
                if (empty($deletedAt)) {
                    $deletedAt = "'" . date('Y-m-d H:i:s') . "'";
                }
                $vals[':deleted_at'] = $deletedAt;
            }
            $queries[] = str_replace([':table', ':ids', ':deleted_at'], $vals, $query);
        }
        $this->bulk($queries);
    }

    public function replace($table, $mapping, $row, $toString = false)
    {
        $colsUnique = [];
        foreach ($mapping as $mongoField => $rdbField) {
            $colsUnique[$rdbField] = uniqid("col_{$rdbField}");
        }
        $query = "REPLACE INTO `{$table}`(`" . implode('`,`', array_values($mapping)) . '`) VALUES (:' . implode(',:', array_values($colsUnique)) . ')';
        $vals = [];
        $cols = [];
        foreach ($mapping as $mongoField => $rdbField) {
            $col = ":{$colsUnique[$rdbField]}";
            if (isset($row->$mongoField)) {
                $vals[$col] = "'" . (string) $row->$mongoField . "'";
            } else {
                $vals[$col] = 'null';
            }
            $cols[] = $col;
        }
        return $toString ? str_replace($cols, $vals, $query) : $this->getClient()->exec(str_replace($cols, $vals, $query));
    }

    public function createTable($table, $schemaColumns, $meta = ' ENGINE=InnoDB DEFAULT CHARSET=utf8;', $toString = false)
    {
        $columns = [];
        foreach ($schemaColumns as $column) {
            $columns[] = implode(' ', $column);
        }
        $query = "CREATE TABLE IF NOT EXISTS `{$table}` (" . implode(',', $columns) . ')';
        return $toString ? $query : $this->getClient()->exec($query);
    }
}
