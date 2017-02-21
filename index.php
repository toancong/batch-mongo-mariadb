<?php
require __DIR__ . '/vendor/autoload.php';

/**
 * Loads environment
 */
try {
    (new Dotenv\Dotenv(__DIR__))->load();
} catch (Exception $e) {
    \BatchMongoRDB\Core\Util::log($e);
    throw new $e;
}

// Migration
echo \BatchMongoRDB\Core\PhinxHelper::init()->runMigrate();

// Gets DB helpers
$mongoHelper = new \BatchMongoRDB\Core\MongoHelper();
$rdbHelper = new \BatchMongoRDB\Core\RDBHelper();

// Run jobs
$runner = new \BatchMongoRDB\Core\JobRunner($mongoHelper, $rdbHelper);
$runner->process();
