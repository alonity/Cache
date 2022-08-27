<?php

/**
 * Cache class
 *
 *
 * @author Qexy admin@qexy.org
 *
 * @copyright © 2022 Alonity
 *
 * @package alonity\cache
 *
 * @license MIT
 *
 * @version 1.0.0
 *
 */

namespace alonity\cache;

use alonity\cache\Storage\CacheStorageInterface;

class Cache {
    const VERSION = '1.0.0';

    public static $config = [
        'storage_directory' => './src/Storage',
        'storage_namespace' => 'alonity\\cache\\Storage\\',
        'storage' => 'file',
    ];

    private static $storages = [];

    public static $error = "";



    public static function setStorage(CacheStorageInterface $storage) {
        self::$storages[$storage->name()] = $storage;
    }



    public static function getStorage() : ?CacheStorageInterface {

        $name = self::$config['storage'];

        if(isset(self::$storages[$name])){ return self::$storages[$name]; }

        $file = ucwords($name, '-');

        $filename = self::$config['storage_directory']."/{$file}.php";

        if(!is_file($filename)){
            self::$error = "Cache storage file {$filename} not found";

            return null;
        }

        $classname = self::$config['storage_namespace'].$file;

        self::$storages[$name] = new $classname(self::$config);

        return self::$storages[$name];
    }



    public static function get(string $name){
        $storage = self::getStorage();

        if(is_null($storage)){ return null; }

        return $storage->get($name);
    }



    public static function has(string $name) : bool {
        $storage = self::getStorage();

        if(is_null($storage)){ return false; }

        return $storage->has($name);
    }



    public static function key($name) : string {
        return md5($name);
    }



    public static function save(string $name, $value) : bool {
        $storage = self::getStorage();

        if(is_null($storage)){ return false; }

        return $storage->save($name, $value);
    }



    public static function delete(string $name) : bool {
        $storage = self::getStorage();

        if(is_null($storage)){ return false; }

        return $storage->delete($name);
    }
}