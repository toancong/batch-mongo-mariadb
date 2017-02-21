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
$jobs = \BatchMongoRDB\Core\ConsoleHelper::getJobs();
if (!empty($jobs)) {
    // Gets DB helpers
    $mongoHelper = new \BatchMongoRDB\Core\MongoHelper();
    $rdbHelper = new \BatchMongoRDB\Core\RDBHelper();

    // Runs jobs
    $runner = new \BatchMongoRDB\Core\JobRunner($mongoHelper, $rdbHelper, $jobs);
    $runner->process();
} else {
    echo 'No jobs. Exit!';
}
