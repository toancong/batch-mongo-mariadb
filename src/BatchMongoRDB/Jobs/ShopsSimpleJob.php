<?php
namespace BatchMongoRDB\Jobs;

/**
 *
 */
class ShopsSimpleJob extends SimpleJob
{
    public function getMappingSchemeConfig()
    {
        return [
            'table' => ['shops' => 'shops'],
            'columns' => [
                '_id' => ['id', 'varchar(24) primary key NOT NULL'],
                'name' => ['name', 'varchar(255)'],
                'name_kana' => ['name_kana', 'varchar(255)'],
                'updated_at' => ['updated_at', 'datetime'],
            ],
        ];
    }
}
