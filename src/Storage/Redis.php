<?php

/**
 * Cache redis storage class
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

class Redis implements CacheStorageInterface {

    private $instance;

    private $cache = [];

    private $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2,
        'username' => '',
        'password' => '',
        'database' => 0,
        'table' => 'alonity_cache',
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
        return 'redis';
    }

    private function getInstance() : ?\Redis {

        if(!is_null($this->instance)){ return $this->instance; }

        if(!class_exists('\Redis')){
            Cache::$error = "Class Redis not found";

            return null;
        }

        $instance = new \Redis();

        $options = $this->getOptions();

        try {
            $connect = @$instance->connect(
                $options['host'],
                $options['port'],
                $options['timeout'],
                null,
                0,
                $options['timeout']
            );

            if(!$connect){
                Cache::$error = "Error connection to redis: ".$instance->getLastError();

                return null;
            }

            $instance->ping();
        }catch(\RedisException $e){

            Cache::$error = "Redis exception: {$e->getMessage()}";

            return null;
        }

        $opts = [];

        if(!empty($options['password'])){
            $opts['pass'] = $options['password'];
        }

        if(!empty($options['username'])){
            $opts['user'] = $options['username'];
        }

        $auth = empty($opts) || @$instance->auth($opts);

        if(!$auth){
            Cache::$error = "Redis auth error: ".$instance->getLastError();
            return null;
        }

        $select = @$instance->select($options['database']);

        if(!$select){
            Cache::$error = "Redis change database error: ".$instance->getLastError();

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

        $instance = $this->getInstance();

        if(is_null($instance)){
            return null;
        }

        $get = $instance->hGet($options['table'], $name);

        if($get === false){
            return null;
        }

        $json = @json_decode($get, true);

        if(!is_array($json)){
            return null;
        }

        $this->cache[$key] = $json['value'];

        return $this->cache[$key];
    }

    public function save(string $name, $value) : bool {
        $options = $this->getOptions();

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $data = [
            'date' => date('d.m.Y H:i:s'),
            'value' => $value
        ];

        $instance = $this->getInstance();

        if(is_null($instance)){
            return false;
        }

        $set = @$instance->hSet($options['table'], $name, json_encode($data));

        if($set === false){
            Cache::$error = $instance->getLastError();

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

        $instance = $this->getInstance();

        if(is_null($instance)){
            return false;
        }

        return $instance->hExists($options['table'], $name);
    }

    public function delete(string $name) : bool {
        $options = $this->getOptions();

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $instance = $this->getInstance();

        if(is_null($instance)){
            return false;
        }

        $delete = @$instance->hDel($options['table'], $name);

        if($delete === false){
            Cache::$error = $instance->getLastError();

            return false;
        }

        if(isset($this->cache[$key])){
            unset($this->cache[$key]);
        }

        return true;
    }
}