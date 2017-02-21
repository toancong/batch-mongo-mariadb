<?php
namespace BatchMongoRDB\Core;
use \BatchMongoRDB\Core\RDBHelper;
use \BatchMongoRDB\Core\MongoHelper;

/**
 * Abstract for job
 */
abstract class AbstractJob
{

  public $rdbConnection;
  public $mongoConnection;

  public function __construct()
  {
  }

  public function connectDBs()
  {
    $this->rdbConnection = new RDBHelper;
    $this->mongoConnection = new MongoHelper;
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
  public abstract function doReplace();

  /**
   * Do delete task
   * @return boolean result success or fail
   */
  public abstract function doDelete();

  /**
   * Do something after done task
   */
  public abstract function done();

  public abstract function getCollectionName();
  public abstract function getTableName();
}
