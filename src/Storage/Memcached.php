<?php

/**
 * Cache memcached storage class
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

class Memcached implements CacheStorageInterface {

    private $instance;

    private $cache = [];

    private $options = [
        'host' => '127.0.0.1',
        'port' => 11211,
        'weight' => 0,
        'prefix' => 'alonity_cache_',
        'username' => '',
        'password' => '',
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
        return 'memcached';
    }

    private function getInstance() : ?\Memcached {

        if(!is_null($this->instance)){ return $this->instance; }

        if(!class_exists('\Memcached')){
            Cache::$error = "Memcached not installed";

            return null;
        }

        $instance = new \Memcached();

        $options = $this->getOptions();

        $connect = @$instance->addServer(
            $options['host'],
            $options['port'],
            $options['weight']
        );

        if(!$connect){
            Cache::$error = "Error connection to memcached";

            return null;
        }

        if(!empty($options['password']) || !empty($options['username'])){
            $instance->setSaslAuthData($options['username'], $options['password']);
        }

        $this->instance = $instance;

        return $this->instance;
    }

    public function get(string $name) {

        $options = $this->getOptions();

        $name = $options['prefix'].$name;

        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return $this->cache[$key]; }

        if($options['hashing']){
            $name = $key;
        }

        $instance = $this->getInstance();

        if(is_null($instance)){
            return null;
        }

        $get = $instance->get($name);

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

        $name = $options['prefix'].$name;

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

        $set = @$instance->set($name, json_encode($data));

        if($set === false){
            Cache::$error = $instance->getResultMessage();

            return false;
        }

        $this->cache[$key] = $value;

        return true;
    }

    public function has(string $name) : bool {
        $options = $this->getOptions();

        $name = $options['prefix'].$name;

        $key = Cache::key($name);

        if(isset($this->cache[$key])){ return true; }

        if($options['hashing']){
            $name = $key;
        }

        $instance = $this->getInstance();

        if(is_null($instance)){
            return false;
        }

        return $instance->get($name) !== false;
    }

    public function delete(string $name) : bool {
        $options = $this->getOptions();

        $name = $options['prefix'].$name;

        $key = Cache::key($name);

        if($options['hashing']){
            $name = $key;
        }

        $instance = $this->getInstance();

        if(is_null($instance)){
            return false;
        }

        $delete = @$instance->delete($name);

        if($delete === false){
            Cache::$error = $instance->getResultMessage();

            return false;
        }

        if(isset($this->cache[$key])){
            unset($this->cache[$key]);
        }

        return true;
    }
}