<?php

if (!function_exists('setCookie')) {
    /**
     * Sets a cookie with the specified options.
     *
     * @param string      $name      The name of the cookie
     * @param string      $value     The value of the cookie
     * @param string      $expire    The expiration date of the cookie (optional)
     * @param string      $domain    The domain of the cookie (optional)
     * @param string      $path      The path of the cookie (optional)
     * @param string      $prefix    The prefix for the cookie name (optional)
     * @param bool        $secure    Specifies whether the cookie should only be transmitted over a secure HTTPS connection (optional)
     * @param bool        $httpOnly  Specifies whether the cookie should be accessible only through the HTTP protocol (optional)
     * @param string|null $sameSite  The SameSite attribute for the cookie (optional)
     */
    function setCookie(
        string $name,
        string $value  = '',
        string $expire = '',
        string $domain = '',
        string $path   = '/',
        string $prefix = '',
        bool $secure   = false,
        bool $httpOnly = false,
        ?string $sameSite = null
    ) {
        Axm::app()->response->setCookie($name, $value, $expire, $domain, $path, $prefix, $secure, $httpOnly, $sameSite);
    }
}

if (!function_exists('getCookie')) {

    /**
     * Retrieves the value of a cookie.
     *
     * @param string $name      The name of the cookie
     * @param bool   $xssClean  Specifies whether to sanitize the value for protection against cross-site scripting (optional)
     * @return string|null The value of the cookie or null if not found
     */
    function getCookie(string $name, bool $xssClean = false): ?string
    {
        $request = Axm::app()->request;
        $prefix  = $request->getCookiePrefix();

        if (isset($_COOKIE[$name])) {
            $cookieValue = $_COOKIE[$name];
        } else {
            $cookieValue = $request->getCookie($prefix . $name);
        }

        if ($xssClean) {
            $cookieValue = filter_var($cookieValue, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $cookieValue;
    }
}

if (!function_exists('deleteCookie')) {
    /**
     * Deletes a cookie with the specified options.
     *
     * @param string $name   The name of the cookie
     * @param string $domain The domain of the cookie (optional)
     * @param string $path   The path of the cookie (optional)
     * @param string $prefix The prefix for the cookie name (optional)
     */
    function deleteCookie(string $name, string $domain = '', string $path = '/', string $prefix = '')
    {
        Axm::app()->response->deleteCookie($name, $domain, $path, $prefix);
    }
}

if (!function_exists('hasCookie')) {
    /**
     * Checks if a cookie exists with the specified options.
     *
     * @param string      $name   The name of the cookie
     * @param string|null $value  The value to match (optional)
     * @param string      $prefix The prefix for the cookie name (optional)
     *
     * @return bool True if the cookie exists, false otherwise
     */
    function hasCookie(string $name, ?string $value = null, string $prefix = ''): bool
    {
        return Axm::app()->response->hasCookie($name, $value, $prefix);
    }
}
