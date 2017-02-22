<?php

class ExampleSimpleJobTest extends TestCase
{
    public $job;

    public function setup()
    {
        $mongoHelper = new \BatchMongoRDB\Core\MongoHelper;
        $rdbHelper = new \BatchMongoRDB\Core\RDBHelper;
        $this->job = new \BatchMongoRDB\Jobs\ExampleSimpleJob($mongoHelper, $rdbHelper);
    }

    public function testGetCollectionNames()
    {
        $this->assertInternalType('array', $this->job->getCollectionNames());
        $this->assertEquals(['app_versions'], $this->job->getCollectionNames());
    }

    public function testGetCollectionFields()
    {
        $this->assertInternalType('array', $this->job->getCollectionFields());
        $this->assertEquals([
            '_id', 'platform', 'version', 'name', 'enviroment'
        ], $this->job->getCollectionFields());
    }

    public function testGetTableNames()
    {
        $this->assertInternalType('array', $this->job->getTableNames());
        $this->assertEquals(['app_versions'], $this->job->getTableNames());
    }

    public function testGetTableColumns()
    {
        $this->assertInternalType('array', $this->job->getTableColumns());
        $this->assertEquals([
            'id', 'platform', 'version', 'name', 'enviroment'
        ], $this->job->getTableColumns());
    }

    public function testGetTableColumnsWithMeta()
    {
        $this->assertInternalType('array', $this->job->getTableColumnsWithMeta());
        $this->assertEquals( [
            ['id', 'varchar(24) primary key NOT NULL'],
            ['platform', 'varchar(10)'],
            ['version', 'varchar(20)'],
            ['name', 'varchar(50)'],
            ['enviroment', 'varchar(20)'],
        ], $this->job->getTableColumnsWithMeta());
    }
}
