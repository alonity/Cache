<?php

/**
 * Cache storage interface
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

interface CacheStorageInterface {

    /**
     * @param array $options
    */
    public function __construct(array $options);



    /**
     * Set storage options
    */
    public function setOptions(array $options);



    /**
     * Return storage options
     *
     * @return array
    */
    public function getOptions() : array;



    /**
     * Return unique storage name in lower case
     *
     * @return string
    */
    public function name() : string;



    /**
     * Return cache by name
     * Returned NULL if cache not exists
     *
     * @param string $name
     *
     * @return mixed
    */
    public function get(string $name);



    /**
     * Save cache by name in storage
     *
     * @param string $name
     *
     * @param mixed $value
     *
     * @return boolean
    */
    public function save(string $name, $value) : bool;



    /**
     * Check cache exists in storage
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has(string $name) : bool;



    /**
     * Delete cache from storage
     *
     * @param string $name
     *
     * @return boolean
     */
    public function delete(string $name) : bool;

}