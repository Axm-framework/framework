<?php

declare(strict_types=1);

namespace App\Middlewares;

use Fiber;
use App\Middlewares\BaseMiddleware;


/**
 * Class RateLimiterMiddleware
 * This class provides rate limiting functionality for API requests.
 */
class RateLimiterMiddleware extends BaseMiddleware
{
    private int $maxRequestsPerSecond;
    private float $lastRequestTime;
    private int $maxBurstRequests = 10;
    private int $burstRequestCount = 0;
    private int $burstRequestsPerRequest = 1;
    private int $lastBurstReset = 0;
    private Fiber $rateLimiterFiber;
    private array $beforeLimitingHooks;

    /**
     * RateLimiter constructor.
     *
     * @param int $maxRequestsPerSecond Maximum number of requests per second.
     * @param int $burstRequestsPerRequest Number of burst requests allowed per single request.
     */
    public function __construct(int $maxRequestsPerSecond, int $burstRequestsPerRequest = 1)
    {
        $this->maxRequestsPerSecond = $maxRequestsPerSecond;
        $this->lastRequestTime = microtime(true);
        $this->burstRequestsPerRequest = $burstRequestsPerRequest;

        $this->rateLimiterFiber = new Fiber(function () {
            while (true) {
                $currentTime = microtime(true);
                $timeSinceLastRequest = $currentTime - $this->lastRequestTime;

                if ($timeSinceLastRequest < 1 / $this->maxRequestsPerSecond) {
                    usleep((int)((1 / $this->maxRequestsPerSecond - $timeSinceLastRequest) * 1000000));
                }

                $this->lastRequestTime = microtime(true);
                Fiber::suspend();
            }
        });

        $this->rateLimiterFiber->start();
    }

    /**
     * Resets the burst counter if needed.
     * @return void
     */
    private function resetBurstIfNeeded()
    {
        if (time() - $this->lastBurstReset > 60) {
            $this->burstRequestCount = 0;
            $this->lastBurstReset = time();
        }
    }

    /**
     * Adds a hook/event before applying limiting.
     *
     * @param callable $beforeLimitingHook Function to execute before 
     * applying the limitation.
     */
    public function addBeforeLimitingHook(callable $beforeLimitingHook)
    {
        $this->beforeLimitingHooks[] = $beforeLimitingHook;
    }

    /**
     * Makes a request, applying the limitation and executing events/hooks.
     * @param callable $apiCall API call function to be executed.
     */
    public function makeRequest(callable $apiCall)
    {
        $this->resetBurstIfNeeded();

        foreach ($this->beforeLimitingHooks as $beforeLimitingHook) {
            call_user_func($beforeLimitingHook);
        }

        for ($i = 0; $i < $this->burstRequestsPerRequest; $i++) {
            if ($this->burstRequestCount < $this->maxBurstRequests) {
                $this->burstRequestCount++;
                $apiCall();
            } else {
                $this->rateLimiterFiber->resume();
                $apiCall();
            }
        }
    }

    /**
     * @return void
     */
    public function execute()
    {
        new self(10);
    }
}
