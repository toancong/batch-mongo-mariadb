<?php
namespace BatchMongoRDB\Jobs;

use BatchMongoRDB\Core\AbstractJob;

/**
 *
 */
abstract class SimpleJob extends AbstractJob
{
    public function init()
    {
        $tables = $this->getTableNames();
        if (!$tables) {
            return;
        }
        $metaColumns = $this->getTableColumnsWithMeta();
        $queries = [];
        foreach ($tables as $table) {
            $queries[] = $this->rdbHelper->createTable($table, $metaColumns, null, true);
        }
        $this->rdbHelper->exec($queries);
    }


    public function doReplace($data)
    {
        $tables = $this->getTableNames();
        if (!$tables) {
            return;
        }

        $queries = [];
        foreach ($tables as $table) {
            $mapping = array_combine($this->getCollectionFields(), $this->getTableColumns());
            foreach ($data[$table] as $row) {
                $queries[] = $this->rdbHelper->replace($table, $mapping, $row, true);
            }
        }

        return $this->rdbHelper->bulk($queries);
    }

    public function doDelete($data)
    {
    }

    public function done()
    {
    }
}
