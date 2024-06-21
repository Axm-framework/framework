<?php

namespace Http;

trait ProxyTrait
{

    /**
     * Sets the proxy for the cURL request.
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
     */
    public function tunnelThroughProxy(int $tunnel = 1)
    {
        curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, $tunnel);
        return $this;
    }
}
