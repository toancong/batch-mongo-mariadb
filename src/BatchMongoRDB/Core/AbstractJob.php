<?php
namespace BatchMongoRDB\Core;

/**
 * Abstract for job
 */
abstract class AbstractJob
{
  /**
   * Job's name
   * @var string
   */
  public $name;

  public function __construct($name)
  {
    $this->name = $name;
  }

  /**
   * Return an array for config mapping fields mongo to rdb
   * @return array config mapping table
   */
  public abstract function getMappingSchemeConfig();

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
}
