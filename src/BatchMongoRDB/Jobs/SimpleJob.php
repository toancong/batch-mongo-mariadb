<?php
namespace BatchMongoRDB\Jobs;
use \BatchMongoRDB\Core\AbstractJob;
use \BatchMongoRDB\Core\RDBHelper;
use \BatchMongoRDB\Core\Util;

/**
 *
 */
abstract class SimpleJob extends AbstractJob
{

  public function doReplace()
  {
    $config = $this->getMappingSchemeConfig();
    if (!$config) {
      return;
    }

    $this->connectDBs();

    $this->rdbConnection->createTable(array_values($config['table'])[0], array_values($config['columns']));

  }


  public function doDelete()
  {
  }

  public function done()
  {

  }
}
