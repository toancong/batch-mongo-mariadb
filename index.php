<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/MongoHelper.php';
require __DIR__ . '/RDBHelper.php';

// Gets DB helpers
$mongoHelper = new MongoHelper('mongodb://mongo:27017', 'dbmongo');
$rdbHelper = new RDBHelper('mysql:host=mysql;dbname=dbmysql', 'dbuser', '123456');

// Initials MySQL
$rdbHelper->init();

// Gets meta data
// $oldMeta is the meta for current process
// $newMeta is the meta for the next process
$oldMeta = $rdbHelper->getMeta();
$newMeta = $oldMeta;

// Processes
while (true) {
    // Resets flags
    $hasNewData = false;

    // Gets update data from mongoDB
    list($updatedData, $updatedMeta) = $mongoHelper->getUpdatedData($oldMeta);
    if (!empty($updatedData)) {
        // Changes flag value
        $hasNewData = true;

        // Adds new meta
        foreach ($updatedMeta as $collectionName => $meta) {
            $newMeta[$collectionName]['last_updated_at'] = $meta['last_updated_at'];
            $newMeta[$collectionName]['last_updated_id'] = $meta['last_updated_id'];
        }
        // @TODO updates data here
    }

    // Gets remove data from mongoDB
    list($deletedData, $deletedMeta) = $mongoHelper->getDeletedData($oldMeta);
    if (!empty($deletedData)) {
        // Changes flag value
        $hasNewData = true;

        // Adds new meta
        foreach ($deletedMeta as $collectionName => $meta) {
            $newMeta[$collectionName]['last_deleted_at'] = $meta['last_deleted_at'];
            $newMeta[$collectionName]['last_deleted_id'] = $meta['last_deleted_id'];
        }
        // @TODO deletes data here
    }

    if ($hasNewData) {
        // Saves meta to RDB for the next process
        $rdbHelper->setMeta($newMeta);

        // Applies new meta to the next process
        $oldMeta = $newMeta;

        // Notices that has new data
        echo "Has data\n";
    }
}
