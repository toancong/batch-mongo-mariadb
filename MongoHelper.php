<?php

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
