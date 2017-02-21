<?php
namespace BatchMongoRDB\Jobs;
use \BatchMongoRDB\Core\AbstractJob;
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
  }


  public function doDelete()
  {
  }
}
