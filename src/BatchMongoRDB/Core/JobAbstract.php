<?php
namespace BatchMongoRDB\Core;

/**
 * Abstract for job
 */
abstract class JobAbstract
{
    public function init()
    {
        PhinxHelper::init()->runMigrate();
    }

    abstract public function replace();
    abstract public function delete();
    abstract public function done();
}
