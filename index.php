<?php

require('vendor/autoload.php');
require('./co.php');

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
            $resolve(255555);
        });
    }));

    yield $nbthings . " is a lot of things !!!!!";
};

$http->on('request', function($req, $res) use (&$gen) {
    co::run($gen)->then(function($value) use(&$req, &$res) {
        $res->writeHead(200);
        $res->end($value);
    });
});

$socket->listen(3000);
$loop->run();
