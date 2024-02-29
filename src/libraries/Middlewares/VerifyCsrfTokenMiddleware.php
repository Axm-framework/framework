<?php

declare(strict_types=1);

namespace Middlewares;

use Exception;
use Encryption\Encrypter;
use App\Middlewares\BaseMiddleware;


class VerifyCsrfTokenMiddleware extends BaseMiddleware
{
    /**
     * The application instance.
     * @var App
     */
    protected $app;

    /**
     * The encrypter implementation.
     * @var Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new middleware instance.
     * @return void
     */
    public function __construct()
    {
        $this->app = app();
        $this->encrypter = new Encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Http\Request $request
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        if ($this->isReading() || $this->isAxmRequest()) {
            return $this->addCookieToResponse();
        }

        throw new Exception('CSRF token mismatch.');
    }

    /**
     * Determine if the HTTP request uses a ‘read’ verb.
     *
     * @param  Http\Request  $request
     * @return bool
     */
    protected function isReading()
    {
        return $this->app->request->isRequestMethod(['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     * @return bool
     */
    protected function isAxmRequest(): bool
    {
        $isAxm = app()->request->isAxmRequest();
        return $isAxm;
    }

    /**
     * Add the CSRF token to the response cookies.
     * @return void
     */
    protected function addCookieToResponse()
    {
        $request = app()->request;
        $config  = config('session');
        $this->newCookie($request, $config);
    }

    /**
     * Create a new "XSRF-TOKEN" cookie that contains the CSRF token.
     *
     * @param  \Axm\Http\Request $request
     * @param  array  $config
     */
    protected function newCookie($request, $config)
    {
        $request->setcookie(
            'XSRF-TOKEN',
            $this->app->getCsrfToken(),
            $config['expiration'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false
        );
    }
}
