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

// Migrates
echo \BatchMongoRDB\Core\PhinxHelper::init()->runMigrate();

// Gets jobs
\BatchMongoRDB\Core\ConsoleHelper::init();
$job = \BatchMongoRDB\Core\ConsoleHelper::getJob();
if (!empty($job)) {
    // Gets DB helpers
    $mongoHelper = new \BatchMongoRDB\Core\MongoHelper();
    $rdbHelper = new \BatchMongoRDB\Core\RDBHelper();

    // Runs job
    $runner = new \BatchMongoRDB\Core\JobRunner($mongoHelper, $rdbHelper, $job);
    $runner->process();
} else {
    echo 'No jobs. Exit!';
}
