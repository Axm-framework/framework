<?php

namespace Axm\Middlewares;

use Fiber;
use Axm\Http\Request;
use Axm\Http\Response;
use Axm\Middlewares\BaseMiddleware;

use Axm\Controller;

/**
 * Middleware to ensure each request is processed in a separate `Fiber`
 *
 * The `Fiber` class has been added in PHP 8.1+, so this middleware is only used
 * on PHP 8.1+. On supported PHP versions, this middleware is automatically
 * added to the list of middleware handlers, so there's no need to reference
 * this class in application code.
 */
class FiberMiddleware extends BaseMiddleware
{
    // public function __invoke(Request $request, callable $next)
    // {
    //     $fiber = new Fiber(function () use ($request, $next) {
    //         $response = $next($request);

    //         $response = yield $response;

    //         return $response;
    //     });

    //     $fiber->start();

    //     if ($fiber->isTerminated()) {
    //         return $fiber->getReturn();
    //     }
    // }


    public function execute(callable $callback)
    {
        $fiber = new Fiber(function () use ($callback) {

            $response = yield $callback();
            return $response;
        });

        // dd(
        //     $fiber
        // );



        $fiber->start();

        if ($fiber->isTerminated()) {
            return $fiber->getReturn();
        }
    }
}
