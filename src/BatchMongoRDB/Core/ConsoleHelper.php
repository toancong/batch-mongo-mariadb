<?php
namespace BatchMongoRDB\Core;

/**
 * Console helper
 */
class ConsoleHelper
{
    protected static $client = null;

    public static function getClient()
    {
        if (static::$client === null) {
            static::$client = new \Commando\Command();
        }
        return static::$client;
    }

    public static function init()
    {
        // Defines a flag "-j" a.k.a. "--job"
        static::getClient()->option('j')
            ->aka('job')
            ->describedAs('Job will be ran');
    }

    public static function getJob()
    {
        return empty(static::getClient()['j']) ? (getenv('DEFAULT_JOBS') ? getenv('DEFAULT_JOBS') : null) : static::getClient()['j'];
    }
}
