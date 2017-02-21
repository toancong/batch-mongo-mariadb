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

        $this->phinxTextWrapper->setOption('configuration', 'phinx.php');
        $this->phinxTextWrapper->setOption('parser', 'php');
        $this->phinxTextWrapper->setOption('environment', getenv('environment') ? getenv('environment') : 'development');
    }

    public function runMigrate()
    {
        $this->phinxTextWrapper->getMigrate();
        return $this->phinxTextWrapper->getStatus();
    }

    public static function init()
    {
        $phinx = new self();

        return $phinx;
    }
}
