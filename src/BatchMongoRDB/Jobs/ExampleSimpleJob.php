<?php
namespace BatchMongoRDB\Jobs;
use \BatchMongoRDB\Core\AbstractJob;
use \BatchMongoRDB\Core\Util;
use \BatchMongoRDB\Core\RDBHelper;

/**
 *
 */
class ExampleSimpleJob extends SimpleJob
{
  public function getCollectionNames()
  {
    return ['app_versions'];
  }

  public function getTableNames()
  {
    return ['app_versions'];
  }

  public function getMappingSchemeConfig()
  {
    return [
      'table' => ['app_versions' => 'app_versions'],
      'columns' => [
        '_id' => ['id', 'varchar(24) primary key NOT NULL'],
        'platform' => ['platform', 'varchar(10)'],
        'version' => ['version', 'varchar(20)'],
        'name' => ['name', 'varchar(50)'],
        'enviroment' => ['enviroment', 'varchar(20)'],
      ]
    ];
  }
}
