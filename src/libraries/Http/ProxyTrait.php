<?php

namespace Http;

trait ProxyTrait
{

    /**
     * Sets the proxy for the cURL request.
     *
     * @param string $proxy The proxy URL.
     * @param string|null $username Optional username for proxy authentication.
     * @param string|null $password Optional password for proxy authentication.
     * @return $this
     */
    public function setProxy(string $proxy, ?string $username = null, ?string $password = null)
    {
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy);

        if ($username !== null && $password !== null) {
            curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, "$username:$password");
        }

        return $this;
    }

    /**
     * Sets the cURL option to tunnel through a proxy.
     *
     * @param int $tunnel The tunnel option (1 to enable, 0 to disable).
     * @return $this
     */
    public function tunnelThroughProxy(int $tunnel = 1)
    {
        curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, $tunnel);
        return $this;
    }
}
