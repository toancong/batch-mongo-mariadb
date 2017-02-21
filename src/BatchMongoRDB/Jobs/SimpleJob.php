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

        $collections = array_keys($this->getMappingSchemeConfig()['table']);
        $queries = [];
        foreach ($collections as $index => $collectionName) {
            $queries[] = $this->rdbHelper->createTable(array_values($config['table'])[$index], array_values($config['columns']), null, true);
            $mapping = [];
            foreach ($this->getMappingSchemeConfig()['columns'] as $mongoField => $item) {
                $mapping[$mongoField] = $item[0];
            }
            foreach ($data[$collectionName] as $row) {
                $queries[] = $this->rdbHelper->replace($this->getMappingSchemeConfig()['table'][$collectionName], $mapping, $row, true);
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
