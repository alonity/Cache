<?php

/**
 * Cache mongodb storage class
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
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\UnexpectedValueException;

class Mongodb implements CacheStorageInterface {

    private $instance, $database, $collection;

    private $cache = [];

    private $options = [
        'url' => 'mongodb://localhost:27017',
        'timeout' => 2,
        'database' => 'alonity_cache',
        'collection' => 'alonity_cache',
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
        return 'mongo';
    }

    private function getDatabase() : ?Database {
        if(!is_null($this->database)){ return $this->database; }

        $instance = $this->getInstance();

        if(is_null($instance)){
            return null;
        }

        $options = $this->getOptions();

        try{
            $database = $instance->selectDatabase($options['database']);
        }catch(InvalidArgumentException $e){

            Cache::$error = "MongoDB select database argument error: {$e->getMessage()}";

            return null;

        }

        $this->database = $database;

        return $this->database;
    }

    private function getCollection() : ?Collection {

        if(!is_null($this->collection)){ return $this->collection; }

        $database = $this->getDatabase();

        if(is_null($database)){ return null; }

        $options = $this->getOptions();


        if(iterator_count($database->listCollections(['filter' => ['name' => $options['collection']]])) <= 0){
            $database->createCollection($options['collection']);
        }

        try{

            $collection = $database->selectCollection($options['collection']);

        }catch(InvalidArgumentException $e){

            Cache::$error = "MongoDB select collection argument error: {$e->getMessage()}";

            return null;

        }

        $this->collection = $collection;

        return $this->collection;
    }

    private function getInstance() : ?Client {

        if(!is_null($this->instance)){ return $this->instance; }

        if(!class_exists('\MongoDB\Driver\Manager')){
            Cache::$error = "MongoDB Driver Manager not installed";

            return null;
        }

        $options = $this->getOptions();

        try {

            $instance = new Client($options['url']);

        }catch(RuntimeException $e){

            Cache::$error = "MongoDB runtime error: {$e->getMessage()}";

            return null;
        }

        $this->instance = $instance;

        return $this->instance;
    }

    public function get(string $name) {
        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return $this->cache[$key]; }

        $options = $this->getOptions();

        if($options['hashing']){
            $name = $key;
        }

        $collection = $this->getCollection();

        if(is_null($collection)){
            return null;
        }

        $get = $collection->findOne(['name' => $name]);

        if(is_null($get)){
            return null;
        }

        $json = @json_decode($get->value, true);

        if(!is_array($json)){
            return null;
        }

        $this->cache[$key] = $json['json'];

        return $this->cache[$key];
    }

    public function save(string $name, $value) : bool {
        $options = $this->getOptions();

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $data = [
            'name' => $name,
            'date' => date('d.m.Y H:i:s'),
            'value' => json_encode(['json' => $value])
        ];

        $collection = $this->getCollection();

        if(is_null($collection)){
            return false;
        }

        try{

            $set = @$collection->replaceOne(['name' => $name], $data, ['upsert' => true]);

            if(is_null($set)){
                Cache::$error = "Error set value";

                return false;
            }

        }catch(UnexpectedValueException $e){
            Cache::$error = "Error set value: {$e->getMessage()}";

            return false;

        }

        $this->cache[$key] = $value;

        return true;
    }

    public function has(string $name) : bool {
        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return true; }

        $options = $this->getOptions();

        if($options['hashing']){
            $name = $key;
        }

        $collection = $this->getCollection();

        if(is_null($collection)){
            return false;
        }

        return !empty($collection->findOne(['name' => $name]));
    }

    public function delete(string $name) : bool {
        $options = $this->getOptions();

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $collection = $this->getCollection();

        if(is_null($collection)){
            return false;
        }

        $delete = @$collection->deleteOne(['name' => $name]);

        if($delete->getDeletedCount() <= 0){
            Cache::$error = "Error deleting value";

            return false;
        }

        if(isset($this->cache[$key])){
            unset($this->cache[$key]);
        }

        return true;
    }
}