<?php

require_once 'vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__))->load();
} catch (Exception $e) {
    \BatchMongoRDB\Core\Util::log($e);
    throw new $e;
}

return [
    "paths" => [
        "migrations" => "%%PHINX_CONFIG_DIR%%/resources/migrations",
        "seeds" => "%%PHINX_CONFIG_DIR%%/resources/seeds"
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_database" => "development",
        "production" => [
            "adapter" => "mysql",
            "host" => getenv('RDB_HOST'),
            "name" => getenv('RDB_DB'),
            "user" => getenv('RDB_USER'),
            "pass" => getenv('RDB_PASS'),
            "port" => 3306,
            "charset" => "utf8"
        ],
        "development" => [
            "adapter" => "mysql",
            "host" => getenv('RDB_HOST'),
            "name" => getenv('RDB_DB'),
            "user" => getenv('RDB_USER'),
            "pass" => getenv('RDB_PASS'),
            "port" => 3306,
            "charset" => "utf8"
        ],
        "testing" => [
            "adapter" => "mysql",
            "host" => getenv('RDB_HOST'),
            "name" => getenv('RDB_DB'),
            "user" => getenv('RDB_USER'),
            "pass" => getenv('RDB_PASS'),
            "port" => 3306,
            "charset" => "utf8"
        ]
    ]
];
