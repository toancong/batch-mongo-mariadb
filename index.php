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

// Gets DB helpers
$mongoHelper = new \BatchMongoRDB\Core\MongoHelper(getenv('MONGODB_URL'), getenv('MONGODB_DB'));
$rdbHelper = new \BatchMongoRDB\Core\RDBHelper(getenv('RDB_URL') . ';dbname=' . getenv('RDB_DB'), getenv('RDB_USER'), getenv('RDB_PASS'));

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
    $isUpdatedFinished = false;
    $isDeletedFinished = false;

    // Gets update data from mongoDB
    list($updatedData, $updatedMeta) = $mongoHelper->getUpdatedData($oldMeta);
    if (!empty($updatedData)) {
        // Changes flag value
        $hasNewData = true;

        // Adds new meta
        foreach ($updatedMeta as $collectionName => $meta) {
            if (isset($meta['last_updated_id'])) {
                $newMeta[$collectionName]['last_updated_id'] = $meta['last_updated_id'];
            }
            if (isset($meta['last_updated_at'])) {
                $newMeta[$collectionName]['last_updated_at'] = $meta['last_updated_at'];
                if (!isset($newMeta[$collectionName]['last_updated_at_first'])) {
                    $newMeta[$collectionName]['last_updated_at_first'] = $meta['last_updated_at'];
                }
            }
        }
        // @TODO updates data here
    } else {
        // Finished, resets meta for new update data
        $isUpdatedFinished = true;
        foreach ($newMeta as $collectionName => $meta) {
            unset($newMeta[$collectionName]['last_updated_id']);
            unset($newMeta[$collectionName]['last_updated_at_first']);
        }
    }

    // Gets remove data from mongoDB
    list($deletedData, $deletedMeta) = $mongoHelper->getDeletedData($oldMeta);
    if (!empty($deletedData)) {
        // Changes flag value
        $hasNewData = true;

        // Adds new meta
        foreach ($deletedMeta as $collectionName => $meta) {
            if (isset($meta['last_deleted_id'])) {
                $newMeta[$collectionName]['last_deleted_id'] = $meta['last_deleted_id'];
            }
            if (isset($meta['last_deleted_at'])) {
                $newMeta[$collectionName]['last_deleted_at'] = $meta['last_deleted_at'];
                if (!isset($newMeta[$collectionName]['last_deleted_at_first'])) {
                    $newMeta[$collectionName]['last_deleted_at_first'] = $meta['last_deleted_at'];
                }
            }
        }
        // @TODO deletes data here
    } else {
        // Finished, resets meta for new delete data
        $isDeletedFinished = true;
        foreach ($newMeta as $collectionName => $meta) {
            unset($newMeta[$collectionName]['last_deleted_id']);
            unset($newMeta[$collectionName]['last_deleted_at_first']);
        }
    }

    if ($hasNewData || $isUpdatedFinished || $isDeletedFinished) {
        // Saves meta to RDB for the next process
        $rdbHelper->setMeta($newMeta);

        // Applies new meta to the next process
        $oldMeta = $newMeta;

        // Notices that has new data
        if ($hasNewData) {
            echo "Has data\n";
        }
    }
}
