<?php
namespace BatchMongoRDB\Core;

use BatchMongoRDB\Core\MongoHelper;
use BatchMongoRDB\Core\RDBHelper;

/**
 * Abstract for job
 */
abstract class AbstractJob
{
    public $rdbConnection;
    public $mongoConnection;

    public function __construct(MongoHelper $mongoHelper, RDBHelper $rdbHelper)
    {
        $this->mongoHelper = $mongoHelper;
        $this->rdbHelper = $rdbHelper;
    }

    /**
     * Return an array for config mapping fields mongo to rdb.
     * Default is empty array for pass initial creation table
     * @return array config mapping table
     */
    public function getMappingSchemeConfig()
    {
        return [];
    }

    /**
     * Do replace task
     * @return boolean result success or fail
     */
    abstract public function doReplace($data);

    /**
     * Do delete task
     * @return boolean result success or fail
     */
    abstract public function doDelete($data);

    /**
     * Do something after done task
     */
    abstract public function done();

    public function init()
    {
    }

    public function getCollectionNames()
    {
        return array_keys($this->getMappingSchemeConfig()['table']);
    }

    public function getCollectionFields()
    {
      return array_keys($this->getMappingSchemeConfig()['columns']);
    }

    public function getTableNames()
    {
        return array_values($this->getMappingSchemeConfig()['table']);
    }

    public function getTableColumns()
    {
        return array_map(function ($a) {
          return $a[0];
        }, array_values($this->getMappingSchemeConfig()['columns']));
    }

    public function getTableColumnsWithMeta()
    {
        return array_values($this->getMappingSchemeConfig()['columns']);
    }
}
