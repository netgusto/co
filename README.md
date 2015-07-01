# co

PHP implementation of [tj/co](https://github.com/tj/co). Uses [generator functions](http://php.net/manual/en/language.generators.overview.php) and co-routines (php>=5.5.0) to handle asynchronicity in an apparently synchronous manner.

## Install

```bash
composer require netgusto/co
```

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

// we hook co to the reactphp eventloop
co::setLoop($loop);

function someAsynchronousAPI() {
    return Promise(function($resolve) {
        setTimeout(function() use(&$resolve) {
            // asynchronously resolved after 10ms; could be anything async !
            $resolve(['one', 'two', 'three', rand()]);
        }, 10);
    });
}

$gen = function() {
    $data = (yield someAsynchronousAPI());

    # php has suspended on "yield"
    # until the promise returned by someAsynchronousAPI() was resolved.
    # Starting from here, we can use $data synchronously !

    yield ['message' => 'Hello World from co !', 'asyncdata' => $data];
};

$http->on('request', function($req, $res) use (&$gen) {
    co::run($gen)->then(function($value) use(&$req, &$res) {
        $res->writeHead(200, ['Content-Type' => 'application/json']);
        $res->end(json_encode($value));
    });
});

echo "Magic happens on http://localhost:3000\n";

$socket->listen(3000);
$loop->run();
```

## Roadmap

- [ ] Tests
- [ ] Error management
- [ ] Port koajs to php :)
