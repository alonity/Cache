# Cache
Cache component

## Install

`composer require alonity/cache`

### Examples
```php
use alonity\cache\Cache;

require('vendor/autoload.php');

require_once('../vendor/autoload.php');

/**
 * Set storage
 * Default value: file
 * Supported storages: file, mongodb, redis, memcached, memcache
*/
Cache::$config['storage'] = 'mongodb';

// Set save path for file storage 
Cache::$config['path'] = __DIR__.'/tmp/cache';

$storage = Cache::getStorage();

// Check storage if defined
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
```

Documentation: https://alonity.gitbook.io/alonity/components/cache