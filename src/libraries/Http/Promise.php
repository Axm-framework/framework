<?php

declare(strict_types=1);

namespace Http;

use Throwable;
use TypeError;

interface PromiseInterface
{
    public function then(callable $onFulfilled, callable $onRejected = null): PromiseInterface;
    public function resolve($result): PromiseInterface;
    public function reject($reason): PromiseInterface;
    public static function async(callable $callback): PromiseInterface;
    public static function all(array $promises): PromiseInterface;
    public function finally(callable $onFinally): PromiseInterface;
}

class Promise implements PromiseInterface
{
    private const PENDING = 'pending';
    private const FULFILLED = 'fulfilled';
    private const REJECTED = 'rejected';

    private $state = self::PENDING;
    private $result;
    private $callbacks = [];

    /**
     * Creates a new promise.
     */
    public function __construct()
    {
    }

    /**
     * Attaches a callback to be executed when the promise is fulfilled or rejected.
     */
    public function then(callable $onFulfilled, callable $onRejected = null): PromiseInterface
    {
        $this->validateThenArguments($onFulfilled, $onRejected);

        if ($this->state === self::PENDING) {
            $this->callbacks[] = ['onFulfilled' => $onFulfilled, 'onRejected' => $onRejected];
            return $this;
        }

        $callback = ($this->state === self::FULFILLED) ? $onFulfilled : $onRejected;

        if ($callback) {
            try {
                $result = $callback($this->result);
                $this->handleCallbackResult($result);
            } catch (Throwable $th) {
                $this->reject($th instanceof TypeError ? 'Invalid callback' : $th->getMessage());
            }
        }

        return $this;
    }

    /**
     * Handles the result of a callback.
     */
    private function handleCallbackResult($result)
    {
        if ($result instanceof self) {
            $result->then(
                function ($value) {
                    $this->resolve($value);
                },
                function ($reason) {
                    $this->reject($reason);
                }
            );
        } else {
            $this->resolve($result);
        }
    }

    /**
     * Resolves the promise with a value.
     */
    public function resolve(mixed $result): PromiseInterface
    {
        $this->validateResolveOrRejectArgument($result);

        if ($this->state !== self::PENDING) {
            return $this;
        }

        $this->state = self::FULFILLED;
        $this->result = $result;

        foreach ($this->callbacks as $callback) {
            $callback['onFulfilled']($result);
        }

        $this->callbacks = [];

        return $this;
    }

    /**
     * Rejects the promise with a reason.
     */
    public function reject(mixed $reason): PromiseInterface
    {
        $this->validateResolveOrRejectArgument($reason);

        if ($this->state !== self::PENDING) {
            return $this;
        }

        $this->state = self::REJECTED;
        $this->result = $reason;

        foreach ($this->callbacks as $callback) {
            $callback['onRejected']($reason);
        }

        $this->callbacks = [];

        return $this;
    }

    /**
     * Creates a promise that is resolved with the result of a callback.
     */
    public static function async(callable $callback): PromiseInterface
    {
        self::validateAsyncCallback($callback);

        $promise = new self();

        try {
            $response = $callback();
        } catch (Throwable $th) {
            $promise->reject($th);
        }

        $promise->resolve($response);

        return $promise;
    }

    /**
     * Waits for multiple promises to settle and returns an array of their results.
     */
    public static function all(array $promises): PromiseInterface
    {
        self::validatePromisesArray($promises);

        $results = [];
        $count = count($promises);

        if ($count === 0) {
            return self::resolve($results);
        }

        foreach ($promises as $promise) {
            $promise->then(
                function ($value) use (&$results, &$count) {
                    $results[] = $value;
                    if (--$count === 0) {
                        $this->resolve($results);
                    }
                },
                function ($reason) use (&$count) {
                    $this->reject($reason);
                }
            );
        }

        return new static();
    }

    /**
     * Attaches a callback to be executed when the promise settles, regardless of whether it is fulfilled or rejected.
     */
    public function finally(callable $onFinally): PromiseInterface
    {
        $this->validateFinallyCallback($onFinally);

        return $this->then(
            function ($result) use ($onFinally) {
                try {
                    $onFinally();
                } catch (Throwable $th) {
                    throw $th;
                }
                return $result;
            },
            function ($reason) use ($onFinally) {
                $onFinally();
                throw $reason;
            }
        );
    }

    /**
     * Validates the arguments passed to the `then` method.
     */
    private function validateThenArguments(callable $onFulfilled, callable $onRejected = null): void
    {
        if (!is_callable($onFulfilled)) {
            throw new TypeError('onFulfilled must be callable');
        }

        if ($onRejected !== null && !is_callable($onRejected)) {
            throw new TypeError('onRejected must be callable or null');
        }
    }

    /**
     * Validates the argument passed to the `resolve` and `reject` methods.
     */
    private function validateResolveOrRejectArgument(mixed $argument): void
    {
        if ($argument === null || is_scalar($argument)) {
            return;
        }

        if (is_object($argument) && !($argument instanceof self)) {
            throw new TypeError('Argument must be a scalar or a Promise instance');
        }
    }

    /**
     * Validates the callback passed to the `async` method.
     */
    private static function validateAsyncCallback(callable $callback): void
    {
        if (!is_callable($callback)) {
            throw new TypeError('Callback must be callable');
        }
    }

    /**
     * Validates the array of promises passed to the `all` method.
     */
    private static function validatePromisesArray(array $promises): void
    {
        foreach ($promises as $promise) {
            if (!$promise instanceof self) {
                throw new TypeError('All elements of the array must be Promise instances');
            }
        }
    }

    /**
     * Validates the callback passed to the `finally` method.
     */
    private function validateFinallyCallback(callable $callback): void
    {
        if (!is_callable($callback)) {
            throw new TypeError('Callback must be callable');
        }
    }
}
