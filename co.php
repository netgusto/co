<?php

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

function is_closure(&$t) { return is_object($t) && ($t instanceof \Closure); }
function is_promise(&$t) { return is_object($t) && method_exists($t, 'then'); }
function is_generator(&$t) { return is_object($t) && ($t instanceof \Generator); }

function setTimeout($func, $ms) { return co::loop()->addTimer(($ms / 1000), $func); }
function setImmediate($func) { return co::loop()->nextTick($func); }
function Promise($body) {

    $deferred = new Deferred();

    $resolve = function($value) use (&$deferred) { return $deferred->resolve($value); };
    $reject = function($value) use (&$deferred) { return $deferred->reject($value); };
    setImmediate(function() use(&$body, &$resolve, &$reject) { return $body($resolve, $reject); });

    return $deferred->promise();
};

/*static*/ class co {

    protected static $_loop = null;

    public function __construct() { throw new \Exception('co is static, and cannot be instanciated'); }

    public static function setLoop(LoopInterface $loop) {
        if(!is_null(static::$_loop)) {
            throw new \Exception('co::setLoop() must be called exactly once !');
        }

        static::$_loop = $loop;
    }

    public static function loop() {
        if(is_null(static::$_loop)) { throw new \Exception('co::setLoop() not called prior to calling co::loop(), which is incorrect.'); }
        return static::$_loop;
    }

    public static function run(&$genfunc) {

        return Promise(function($resolve, $reject) use (&$genfunc) {

            if(is_closure($genfunc)) { $gen = $genfunc(); }
            if(!is_generator($gen)) { return $resolve($gen); }

            $ret = $gen->current();

            $next = function($ret) use(&$gen, &$resolve, &$reject, &$next) {
                if(is_promise($ret)) {
                    $ret->then(function($resolvedValue) use(&$gen, &$resolve, &$reject, &$next) {
                        try {
                            $tempret = $gen->send($resolvedValue);
                        } catch (\Exception $e) {
                            return $reject($e);
                        }

                        if($gen->valid()) {
                            return $next($tempret);
                        } else {
                            return $resolve($resolvedValue);
                        }
                    });
                } else {
                    $tempret = $gen->send($ret);
                    if($gen->valid()) {
                        return $next($tempret);
                    } else {
                        return $resolve($ret);
                    }
                }
            };

            return $next($ret);
        });
    }
}