<?php

use Phinx\Migration\AbstractMigration;

class CreatMetaTable extends AbstractMigration
{
    public function up()
    {
        $users = $this->table('sync_meta');
        $users->addColumn('collection', 'string', array('limit' => 24))
              ->addColumn('last_updated_at', 'biginteger', array('limit' => 20))
              ->addColumn('last_deleted_at', 'biginteger', array('limit' => 20))
              ->addColumn('last_updated_id', 'string', array('limit' => 24))
              ->addColumn('last_deleted_id', 'string', array('limit' => 24))
              ->addColumn('updated_at', 'timestamp', array('null' => true))
              ->addIndex(array('collection'), array('unique' => true))
              ->save();
    }

    public function down()
    {
        $this->dropTable('sync_meta');
    }
}
