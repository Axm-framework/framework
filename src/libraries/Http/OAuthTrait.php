<?php

namespace Http;


trait OAuthTrait
{
    /**
     * Sets the OAuth token for authentication.
     */
    public function setOAuthToken(string $token)
    {
        $this->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        return $this;
    }

    /**
     * Sets the OAuth token secret for authentication.
     */
    public function setOAuthTokenSecret(string $tokenSecret)
    {
        // You can handle token secret if needed
        // For example, you might use it in the request signature calculation for OAuth 1.0a
        return $this;
    }
}
