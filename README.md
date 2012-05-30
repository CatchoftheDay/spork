[![Build Status](https://secure.travis-ci.org/kriswallsmith/spork.png?branch=master)](http://travis-ci.org/kriswallsmith/spork)

Spork: PHP on a Fork
--------------------

```php
<?php

$manager = new Spork\ProcessManager();
$manager->fork(function() {
    // do something in another process!
})->then(function($output, $status) {
    // do something in the parent process when it's done!
});
```

### Example: Upload images to your CDN

Feed an iterator into the process manager and it will break the job into
multiple batches and spread them across many processes.

```php
<?php

$images = new RecursiveDirectoryIterator('/path/to/images');
$images = new RecursiveIteratorIterator($it);

$manager->process($images, function(SplFileInfo $file) {
    // upload this file
});
```
