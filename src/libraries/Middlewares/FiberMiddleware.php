<?php

declare(strict_types=1);

namespace Middlewares;

use Fiber;
use App\Middlewares\BaseMiddleware;

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
    /**
     * @param callable $callback
     */
    public function execute(callable $callback)
    {
        $fiber = new Fiber(function () use ($callback) {

            $response = yield $callback();
            return $response;
        });

        $fiber->start();

        if ($fiber->isTerminated()) {
            return $fiber->getReturn();
        }
    }
}
