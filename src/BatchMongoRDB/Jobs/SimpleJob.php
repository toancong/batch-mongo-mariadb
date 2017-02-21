<?php
namespace BatchMongoRDB\Jobs;

use BatchMongoRDB\Core\AbstractJob;

/**
 *
 */
abstract class SimpleJob extends AbstractJob
{

    public function doReplace($data)
    {
        $config = $this->getMappingSchemeConfig();
        if (!$config) {
            return;
        }

        $tables = $this->getTableNames();
        $queries = [];
        foreach ($tables as $index => $table) {
            $queries[] = $this->rdbHelper->createTable($table, array_values($config['columns']), null, true);
            $mapping = [];
            foreach ($this->getMappingSchemeConfig()['columns'] as $mongoField => $item) {
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
