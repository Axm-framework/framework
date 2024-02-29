<?php

return
    [
        'google'   => [
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri'  => env('GOOGLE_REDIRECT'),
        ],

        'facebook' => [
            'client_id'     => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect_uri'  => env('FACEBOOK_REDIRECT'),
        ],

        'github'   => [
            'client_id'     => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect_uri'  => env('GITHUB_REDIRECT'),
        ],

        'slack'    => [
            'client_id'     => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'redirect_uri'  => env('SLACK_REDIRECT'),
        ],
    ];
