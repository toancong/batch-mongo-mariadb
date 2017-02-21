<?php
namespace BatchMongoRDB\Core;

class MongoHelper
{
    const COLLECTIONS = ['shops', 'user_shops', 'user', 'shifts', 'time_cards', 'holidays', 'business_times', 'request_day_offs', 'breaks'];

    protected $client = null;
    protected $connectionStr;
    protected $db;
    protected static $config = [];

    public static function getConfig()
    {
        if (empty(static::$config)) {
            static::$config = [
                'host' => getenv('MONGODB_HOST'),
                'port' => getenv('MONGODB_PORT'),
                'database' => getenv('MONGODB_DB'),
                'username' => getenv('MONGODB_USER'),
                'password' => getenv('MONGODB_PASS'),
            ];
        }
        return static::$config;
    }

    public function __construct($config = [])
    {
        $config = array_merge(static::getConfig(), $config);
        $conStr = 'mongodb://';
        if (!empty($config['username']) || !empty($config['password'])) {
            if (!empty($config['username'])) {
                $conStr .= $config['username'];
            }
            if (!empty($config['password'])) {
                $conStr .= ":{$config['password']}";
            }
            $conStr .= '@';
        }
        $conStr .= empty($config['host']) ? '127.0.0.1' : $config['host'];
        $conStr .= empty($config['port']) ? ':27017' : ":{$config['port']}";
        $this->connectionStr = $conStr;
        $this->db = empty($config['database']) ? 'db' : $config['database'];
    }

    public function connect($forceNew = false)
    {
        if ($forceNew || $this->client === null) {
            $this->client = (new \MongoDB\Client($this->connectionStr))->{$this->db};
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

    public function getUpdatedData($meta = [], $limit = 1000)
    {
        $results = [];
        foreach (static::COLLECTIONS as $collectionName) {
            $condition = [];
            if (isset($meta[$collectionName]['last_updated_at_first'])) {
                // Fetchs data for next page
                $condition['updated_at'] = ['$gte' => new \MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_updated_at_first'])];
            } elseif (isset($meta[$collectionName]['last_updated_at'])) {
                // Fetchs new data
                $condition['updated_at'] = ['$gt' => new \MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_updated_at'])];
            }
            if (isset($meta[$collectionName]['last_updated_id'])) {
                $condition['_id'] = ['$gt' => new \MongoDB\BSON\ObjectID($meta[$collectionName]['last_updated_id'])];
            }
            $collection = $this->getClient()->$collectionName;
            $items = $collection->find($condition, [
                'sort' => ['_id' => 1],
                'limit' => $limit,
            ]);
            foreach ($items as $item) {
                $results[$collectionName][] = $item;
                $meta[$collectionName]['last_updated_id'] = (string) $item->_id;
                $updatedAt = intval((string) $item->updated_at);
                if (!isset($meta[$collectionName]['last_updated_at']) || ($meta[$collectionName]['last_updated_at'] < $updatedAt)) {
                    $meta[$collectionName]['last_updated_at'] = $updatedAt;
                }
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
            if (isset($meta[$collectionName]['last_deleted_at_first'])) {
                // Fetchs data for next page
                $condition['deleted_at']['$gte'] = new \MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_deleted_at_first']);
            } elseif (isset($meta[$collectionName]['last_deleted_at'])) {
                // Fetchs new data
                $condition['deleted_at']['$gt'] = new \MongoDB\BSON\UTCDateTime($meta[$collectionName]['last_deleted_at']);
            }
            if (isset($meta[$collectionName]['last_deleted_id'])) {
                $condition['_id'] = ['$gt' => new \MongoDB\BSON\ObjectID($meta[$collectionName]['last_deleted_id'])];
            }
            $collection = $this->getClient()->$collectionName;
            $items = $collection->find($condition, [
                'sort' => ['_id' => 1],
                'limit' => $limit,
            ]);
            foreach ($items as $item) {
                $results[$collectionName][] = $item;
                $meta[$collectionName]['last_deleted_id'] = (string) $item->_id;
                $deletedAt = intval((string) $item->deleted_at);
                if (!isset($meta[$collectionName]['last_deleted_at']) || ($meta[$collectionName]['last_deleted_at'] < $deletedAt)) {
                    $meta[$collectionName]['last_deleted_at'] = $deletedAt;
                }
            }
        }
        return [$results, $meta];
    }
}
