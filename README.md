# co

PHP implementation of tj/co. Uses co-routines (php>=5.5.0) to handle asynchronicity in an apparently synchronous manner.

## Example

```php
<?php

require('vendor/autoload.php');

use React\EventLoop\Factory as EventLoopFactory,
    React\Socket\Server as SocketServer,
    React\Http\Server as HttpServer;

$loop = EventLoopFactory::create();
$socket = new SocketServer($loop);
$http = new HttpServer($socket);

co::setLoop($loop);

$gen = function() {

    $nbthings = (yield Promise(function($resolve) {
        setImmediate(function() use(&$resolve) {
            $resolve(123456);
        });
    }));

    yield "Hello World ! " . $nbthings . " is a big number !!!!!";
};

$http->on('request', function($req, $res) use (&$gen) {
    co::run($gen)->then(function($value) use(&$req, &$res) {
        $res->writeHead(200);
        $res->end($value);
    });
});

$socket->listen(3000);
$loop->run();

echo "Magic happens on http://localhost:3000\n";
```
