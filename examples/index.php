<?php

use alonity\cache\Cache;

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once('../vendor/autoload.php');

Cache::$config['storage'] = 'mongodb';
Cache::$config['path'] = __DIR__.'/tmp/cache';

$storage = Cache::getStorage();

if(is_null($storage)){
    exit(Cache::$error);
}

if(!Cache::save('hello', [
    ['id' => 1, 'name' => 'test'],
    ['id' => 2, 'name' => 'test2']
])){
    exit(Cache::$error);
}

var_dump(Cache::get('hello'));

?>