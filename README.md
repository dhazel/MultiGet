
MultiGet
========

Simultaneuos HTTP requests with curl_multi and PHP 5.3+

```php
$mget = new MultiGet_Get();
$mget->request('http://ya.ru')
->on('success', function ($content) {
  // this anonymous function will be called after request is loaded
  // so you can process data before all other downloads ends
  // also you can add new requests from here
});
$mget->go();// waits for downloads and executes callbacks
// at this point all work done...
```

Example
-------

see example.php

Origin
------
This is a refactoring of the `MultiGet` found here: 

    [https://github.com/Yaffle/MultiGet][1]
    
    [1]:https://github.com/Yaffle/MultiGet

This refactored version is restructured to comply with PSR-0 directory
structure, has revised names to be more intuitive as a ZF2 module, and has a
couple minor API changes, as well as complete docblocks.
