<?php

/**
 * Cache file storage class
 *
 *
 * @author Qexy admin@qexy.org
 *
 * @copyright © 2022 Alonity
 *
 * @package alonity\cache\Storage
 *
 * @license MIT
 *
 * @version 1.0.0
 *
 */

namespace alonity\cache\Storage;


use alonity\cache\Cache;

class File implements CacheStorageInterface {

    private $cache = [];

    private $options = [
        'path' => '../../../../tmp/cache',
        'directory_permissions' => 0770,
        'hashing' => false
    ];

    public function __construct(?array $options = null) {
        if(!is_null($options)){
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options) {
        $this->options = array_replace_recursive($this->options, $options);
    }

    public function getOptions() : array {
        return $this->options;
    }

    public function name() : string {
        return 'file';
    }

    public function get(string $name) {
        $name = str_replace('/', '', $name);

        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return $this->cache[$key]; }

        $options = $this->getOptions();

        $filename = $options['hashing'] ? $options['path']."/{$key}.php" : $options['path']."/{$name}.php";

        if(!is_file($filename)){
            return null;
        }

        if(!is_readable($filename)){
            Cache::$error = "Cache file {$filename} is not readable";

            return null;
        }

        $this->cache[$key] = (include($filename));

        return $this->cache[$key];
    }

    public function save(string $name, $value) : bool {
        $name = str_replace('/', '', $name);

        $options = $this->getOptions();

        $key = Cache::key($name);

        $filename = $options['hashing'] ? $options['path']."/{$key}.php" : $options['path']."/{$name}.php";

        $dir = dirname($filename);

        if(!is_dir($dir)){
            $make = @mkdir($dir, $options['directory_permissions'], true);

            if(!$make){
                Cache::$error = "Error making directory {$dir}";

                return false;
            }
        }

        $data = "<?php // {$name} | ".date('d.m.Y H:i:s');
        $data .= PHP_EOL."return ".var_export($value, true).";";

        $put = @file_put_contents($filename, $data);

        if($put === false){
            Cache::$error = "Error saving cache file {$filename}";

            return false;
        }

        $this->cache[$key] = $value;

        return true;
    }

    public function has(string $name) : bool {
        $name = str_replace('/', '', $name);

        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return true; }

        $options = $this->getOptions();

        $filename = $options['hashing'] ? $options['path']."/{$key}.php" : $options['path']."/{$name}.php";

        return is_file($filename);
    }

    public function delete(string $name) : bool {

        $name = str_replace('/', '', $name);

        $options = $this->getOptions();

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $filename = $options['path']."/{$name}.php";

        if(is_file($filename)){
            $del = @unlink($filename);

            if(!$del){
                Cache::$error = "Error deleting cache file {$filename}";

                return false;
            }
        }

        if(isset($this->cache[$key])){
            unset($this->cache[$key]);
        }

        return true;
    }
}