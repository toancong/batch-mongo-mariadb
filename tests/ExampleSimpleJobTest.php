<?php

class ExampleSimpleJobTest extends TestCase
{
  public function testSomething()
  {
    (new BatchMongoRDB\Jobs\ExampleSimpleJob('app_versions'))->doReplace();
    $this->assertEquals(6, 6);
  }
}
