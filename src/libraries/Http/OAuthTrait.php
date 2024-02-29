<?php

namespace Http;


trait OAuthTrait
{
    /**
     * Sets the OAuth token for authentication.
     *
     * @param string $token The OAuth token.
     * @return $this
     */
    public function setOAuthToken(string $token)
    {
        $this->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        return $this;
    }

    /**
     * Sets the OAuth token secret for authentication.
     *
     * @param string $tokenSecret The OAuth token secret.
     * @return $this
     */
    public function setOAuthTokenSecret(string $tokenSecret)
    {
        // You can handle token secret if needed
        // For example, you might use it in the request signature calculation for OAuth 1.0a
        return $this;
    }
}
