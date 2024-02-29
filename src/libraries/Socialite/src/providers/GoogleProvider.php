<?php

namespace Socialite\Providers;

use Http\Curl;

/**
 * GoogleProvider - A provider for Google OAuth2 authentication.
 */
class GoogleProvider
{
    /**
     * Base URL for Google API.
     */
    const API_BASE_URL = 'https://www.googleapis.com/';

    /**
     * URL for authorization.
     */
    const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * URL for obtaining access token.
     */
    const ACCESS_TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';

    /**
     * URL for fetching user information.
     */
    const USER_INFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    /**
     * URL for Google API documentation.
     */
    const API_DOCUMENTATION_URL = 'https://console.cloud.google.com/apis';

    /**
     * Configuration array for the provider.
     * @var array
     */
    private $config;

    /**
     * Constructor.
     * @param array $config Configuration array for the provider.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get the URL for authorization.
     * @return string Authorization URL.
     */
    public function redirect()
    {
        $params = [
            'response_type' => 'code',
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'scope'         => 'openid email profile',
        ];

        $authorizeUrl = self::AUTHORIZE_URL . '?' . http_build_query($params);
        $this->makeRedirect($authorizeUrl);
    }

    /**
     * Get the access token using the provided authorization code.
     *
     * @return string Access token.
     * @throws \RuntimeException If unable to obtain access token.
     */
    public function user()
    {
        $code = $this->getCode();

        if (!empty($code)) {
            try {
                $params = $this->getParams($code);

                $curl = new Curl();
                $response = $curl->post(self::ACCESS_TOKEN_URL, $params);
                $data = json_decode($response['response'], true);
                $userInfoUrl = self::USER_INFO_URL . '?access_token=' . $data['access_token'];
                $userInfoResponse = $curl->get($userInfoUrl);
                $userInfo = json_decode($userInfoResponse['response'], true);

                $curl->close();
            } catch (\Throwable $e) {
                throw new \RuntimeException('Failed to obtain access token: ' . $e->getMessage());
            }

            return (object)$userInfo;
        }
    }

    /**
     * This method is used to retrieve the parameters required for the token request.
     * It takes in a $code parameter which is the authorization code received from the authorization server.
     *
     * @param string $code The authorization code received from the authorization server
     * @return array
     */
    public function getParams($code): array
    {
        $params = [
            'code'          => $code,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];

        return $params;
    }

    /**
     * Define a method to retrieve the 'code' parameter from the request
     * @return mixed
     */
    public function getCode()
    {
        return app()
            ->request
            ->get('code') ?? null;
    }

    /**
     * Perform a redirect to the given URL
     * @param  string $url The redirect location
     */
    protected function makeRedirect(string $url)
    {
        if (!headers_sent())
            app()
                ->response
                ->redirect($url);
    }
}
