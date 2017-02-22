<?php
namespace BatchMongoRDB\Core;
use \BatchMongoRDB\Core\ConsoleHelper;
use \BatchMongoRDB\Core\MongoHelper;
use \BatchMongoRDB\Core\PhinxHelper;
use \BatchMongoRDB\Core\RDBHelper;

/**
 *
 */
class JobRunner
{
    public function __construct(MongoHelper $mongoHelper, RDBHelper $rdbHelper, $job)
    {
        $this->mongoHelper = $mongoHelper;
        $this->rdbHelper = $rdbHelper;
        $this->job = $job;
    }

    private function update($updatedMeta = [])
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

    private function delete($deletedMeta = [])
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

    private function resetUpdate()
    {
        foreach ($this->newMeta as $collectionName => $meta) {
            unset($this->newMeta[$collectionName]['last_updated_id']);
            unset($this->newMeta[$collectionName]['last_updated_at_first']);
        }
    }

    private function resetDelete()
    {
        foreach ($this->newMeta as $collectionName => $meta) {
            unset($this->newMeta[$collectionName]['last_deleted_id']);
            unset($this->newMeta[$collectionName]['last_deleted_at_first']);
        }
    }

    private function shouldReconnect()
    {
        return empty($this->reconnectAfter) ? false : $this->connectionTime >= $this->reconnectAfter;
    }

    private function reconnect()
    {
        // Reconnects to mongodb and RDB
        $this->mongoHelper->reconnect();
        $this->rdbHelper->reconnect();

        // Resets connection time
        $this->connectionTime = 0;
    }

    public function process()
    {
        $this->init();
        $start = time();
        $this->job->init();
        $collections = $this->job->getCollectionNames();
        while (true) {
            // Reconnects to DBs
            $this->connectionTime = time() - $start;
            if ($this->shouldReconnect()) {
                $this->reconnect();
            }

            // Sets value for some stubs
            $hasNewData = false;
            $isUpdatedFinished = false;
            $isDeletedFinished = false;

            // Gets update data from mongoDB
            list($updatedData, $updatedMeta) = $this->mongoHelper->getUpdatedData($collections, $this->oldMeta);
            if (!empty($updatedData)) {
                // Changes flag value
                $hasNewData = true;

                // Adds new meta
                $this->update($updatedMeta);
                // @TODO updates data here
                $this->job->doReplace($updatedData);
            } else {
                // Finished, resets meta for new update data
                $isUpdatedFinished = true;
                $this->resetUpdate();
            }

            // Gets remove data from mongoDB
            list($deletedData, $deletedMeta) = $this->mongoHelper->getDeletedData($collections, $this->oldMeta);
            if (!empty($deletedData)) {
                // Changes flag value
                $hasNewData = true;

                // Adds new meta
                $this->delete($deletedMeta);
                // @TODO deletes data here
                $this->job->doDelete($updatedData);
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

    private function init()
    {
      $this->oldMeta = $this->rdbHelper->getMeta();
      $this->newMeta = $this->oldMeta;

      // Gets reconnect time in env (seconds)
      $this->reconnectAfter = intval(getenv('RECONNECT_AFTER'));
      $this->connectionTime = 0;
    }

    public static function run($job = '')
    {
      // Loads environment
      (new \Dotenv\Dotenv(__DIR__.'/../../../'))->load();

      // priority get job name
      $job = ConsoleHelper::getJob() ?? $job ?? getenv('DEFAULT_JOBS');
      $job = '\\BatchMongoRDB\Jobs\\'.$job;

      if (!class_exists($job)) {
        echo 'No jobs. Exit!';
        return;
      }

      // Connect databases
      $mongoHelper = new MongoHelper;
      $rdbHelper = new RDBHelper;

      // Run migration
      echo PhinxHelper::init()->runMigrate();

      // Create a job
      $job = new $job($mongoHelper, $rdbHelper);

      // Runs job
      $runner = new self($mongoHelper, $rdbHelper, $job);
      $runner->process();
    }
}
