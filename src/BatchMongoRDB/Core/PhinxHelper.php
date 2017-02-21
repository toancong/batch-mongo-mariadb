<?php
namespace BatchMongoRDB\Core;

/**
 * Phinx outside cli
 */
class PhinxHelper
{
    public function __construct()
    {
        $phinxApp = new \Phinx\Console\PhinxApplication();
        $this->phinxTextWrapper = new \Phinx\Wrapper\TextWrapper($phinxApp);

        $this->phinxTextWrapper->setOption('configuration', '../../../phinx.yml');
        $this->phinxTextWrapper->setOption('parser', 'YAML');
        $this->phinxTextWrapper->setOption('environment', getenv('environment') ? getenv('environment') : 'development');
    }

    protected function runMigrate()
    {
        return $this->phinxTextWrapper->getMigrate();
    }

    public static function init()
    {
        $phinx = new self();

        return $phinx;
    }
}
