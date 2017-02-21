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
        // Defines a flag "-j" a.k.a. "--jobs"
        static::getClient()->option('j')
            ->aka('jobs')
            ->describedAs('Jobs will be ran');
    }

    public static function getJobs()
    {
        $jobs = [];
        if (!empty(static::getClient()['j'])) {
            $jobs = explode(',', static::getClient()['j']);
        } else {
            $envJobs = getenv('DEFAULT_JOBS');
            if ($envJobs) {
                $defaultJobs = explode(',', $envJobs);
                if (!empty($defaultJobs)) {
                    $jobs = $defaultJobs;
                }
            }
        }
        return $jobs;
    }
}
