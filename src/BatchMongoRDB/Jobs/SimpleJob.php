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
        $this->rdbHelper->bulk($queries);
    }


    public function doReplace($data)
    {
        $tables = $this->getTableNames();
        if (!$tables) {
            return;
        }

        $mongoFields = $this->getCollectionFields();
        $queries = [];
        foreach ($tables as $table) {
            $mapping = [];
            foreach ($mongoFields as $mongoField => $item) {
                $mapping[$mongoField] = $item[0];
            }
            foreach ($data[$table] as $row) {
                $queries[] = $this->rdbHelper->replace($table, $mapping, $row, true);
            }
        }

        $this->rdbHelper->bulk($queries);
    }

    public function doDelete($data)
    {
    }

    public function done()
    {
    }
}
