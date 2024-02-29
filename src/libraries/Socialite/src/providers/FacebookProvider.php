<?php

namespace Socialite\Providers;

use Http\Curl;

class FacebookProvider
{
    /**
     * @var string
     */
    const VERSION = 'v3.3';

    /**
     * Base URL for Facebook API.
     */
    const API_BASE_URL = 'https://graph.facebook.com';

    /**
     * URL for authorization.
     */
    const AUTHORIZE_URL = 'https://www.facebook.com/' . self::VERSION . '/dialog/oauth';

    /**
     * URL for obtaining access token.
     */
    const ACCESS_TOKEN_URL = self::API_BASE_URL . self::VERSION . '/oauth/access_token';

    /**
     * URL for fetching user information.
     */
    const USER_INFO_URL = self::API_BASE_URL . self::VERSION . '/me';

    /**
     * URL for Facebook API documentation.
     */
    const API_DOCUMENTATION_URL = 'https://developers.facebook.com/docs/graph-api/overview';

    /**
     * @var array
     */
    private $config;

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
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'scope'         => 'email', // Adjust the scope as needed
            'response_type' => 'code',
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
                $response = $curl->get(self::ACCESS_TOKEN_URL . '?' . http_build_query($params));
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
     * @return array
     */
    public function getParams($code): array
    {
        return [
            'code'          => $code,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return app()
            ->request
            ->get('code') ?? null;
    }

    /**
     * @param string $url
     * @return [type]
     */
    public function makeRedirect(string $url)
    {
        if (!headers_sent())
            app()
                ->response
                ->redirect($url);
    }
}
