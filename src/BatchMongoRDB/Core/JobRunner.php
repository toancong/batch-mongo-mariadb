<?php
namespace BatchMongoRDB\Core;

/**
 *
 */
class JobRunner
{
    public function __construct(\BatchMongoRDB\Core\MongoHelper $mongoHelper, \BatchMongoRDB\Core\RDBHelper $rdbHelper)
    {
        $this->mongoHelper = $mongoHelper;
        $this->rdbHelper = $rdbHelper;

        $this->oldMeta = $this->rdbHelper->getMeta();
        $this->newMeta = $this->oldMeta;
    }

    public function update($updatedMeta = [])
    {
        foreach ($updatedMeta as $collectionName => $meta) {
            if (isset($meta['last_updated_id'])) {
                $this->newMeta[$collectionName]['last_updated_id'] = $meta['last_updated_id'];
            }
            if (isset($meta['last_updated_at'])) {
                $this->newMeta[$collectionName]['last_updated_at'] = $meta['last_updated_at'];
                if (!isset($this->newMeta[$collectionName]['last_updated_at_first'])) {
                    $this->newMeta[$collectionName]['last_updated_at_first'] = $meta['last_updated_at'];
                }
            }
        }
    }

    public function delete($deletedMeta = [])
    {
        foreach ($deletedMeta as $collectionName => $meta) {
            if (isset($meta['last_deleted_id'])) {
                $this->newMeta[$collectionName]['last_deleted_id'] = $meta['last_deleted_id'];
            }
            if (isset($meta['last_deleted_at'])) {
                $this->newMeta[$collectionName]['last_deleted_at'] = $meta['last_deleted_at'];
                if (!isset($this->newMeta[$collectionName]['last_deleted_at_first'])) {
                    $this->newMeta[$collectionName]['last_deleted_at_first'] = $meta['last_deleted_at'];
                }
            }
        }
    }

    public function resetUpdate()
    {
        foreach ($this->newMeta as $collectionName => $meta) {
            unset($this->newMeta[$collectionName]['last_updated_id']);
            unset($this->newMeta[$collectionName]['last_updated_at_first']);
        }
    }

    public function resetDelete()
    {
        foreach ($this->newMeta as $collectionName => $meta) {
            unset($this->newMeta[$collectionName]['last_deleted_id']);
            unset($this->newMeta[$collectionName]['last_deleted_at_first']);
        }
    }

    public function process()
    {
        while (true) {
            $hasNewData = false;
            $isUpdatedFinished = false;
            $isDeletedFinished = false;

            // Gets update data from mongoDB
            list($updatedData, $updatedMeta) = $this->mongoHelper->getUpdatedData($this->oldMeta);
            if (!empty($updatedData)) {
                // Changes flag value
                $hasNewData = true;

                // Adds new meta
                $this->update($updatedMeta);
                // @TODO updates data here
            } else {
                // Finished, resets meta for new update data
                $isUpdatedFinished = true;
                $this->resetUpdate();
            }

            // Gets remove data from mongoDB
            list($deletedData, $deletedMeta) = $this->mongoHelper->getDeletedData($this->oldMeta);
            if (!empty($deletedData)) {
                // Changes flag value
                $hasNewData = true;

                // Adds new meta
                $this->delete($deletedMeta);
                // @TODO deletes data here
            } else {
                // Finished, resets meta for new delete data
                $isDeletedFinished = true;
                $this->resetDelete();
            }

            if ($hasNewData || $isUpdatedFinished || $isDeletedFinished) {
                // Saves meta to RDB for the next process
                $this->rdbHelper->setMeta($this->newMeta);

                // Applies new meta to the next process
                $this->oldMeta = $this->newMeta;

                // Notices that has new data
                if ($hasNewData) {
                    echo "Has data\n";
                }
            }
        }
    }
}
