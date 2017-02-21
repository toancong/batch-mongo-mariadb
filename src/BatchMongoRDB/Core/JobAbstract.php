<?php
namespace BatchMongoRDB\Core;

/**
 * Abstract for job
 */
abstract class JobAbstract
{
    abstract public function init();
    abstract public function replace();
    abstract public function delete();
    abstract public function done();
}
