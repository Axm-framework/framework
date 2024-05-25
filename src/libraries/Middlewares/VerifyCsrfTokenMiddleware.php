<?php

declare(strict_types=1);

namespace Middlewares;

use Exception;
use Http\Request;
use Encryption\Encrypter;
use App\Middlewares\BaseMiddleware;


class VerifyCsrfTokenMiddleware extends BaseMiddleware
{
    /**
     * The application instance.
     */
    protected \App $app;

    /**
     * The encrypter implementation.
     */
    protected Encrypter $encrypter;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        $this->app = app();
        $this->encrypter = new Encrypter;
    }

    /**
     * Handle an incoming request.
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
     */
    protected function isReading(): bool
    {
        return $this->app->request->isRequestMethod(['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     */
    protected function isAxmRequest(): bool
    {
        $isAxm = app()->request->isAxmRequest();
        return $isAxm;
    }

    /**
     * Add the CSRF token to the response cookies.
     */
    protected function addCookieToResponse()
    {
        $request = app()->request;
        $config = config('session');
        $this->newCookie($request, $config);
    }

    /**
     * Create a new "XSRF-TOKEN" cookie that contains the CSRF token.
     */
    protected function newCookie(Request $request, array $config)
    {
        $request->setcookie(
            $config['header_name_cookie'],
            $this->app->getCsrfToken(),
            (int) $config['expiration'],
            (string) $config['path'],
            (string) $config['domain'],
            (bool) $config['secure'],
            false
        );
    }
}
