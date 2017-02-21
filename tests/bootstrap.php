<?php
require __DIR__.'/../vendor/autoload.php';
try {
    (new \Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Exception $e) {
    \BatchMongoRDB\Core\Util::log($e);
    throw new $e;
}
