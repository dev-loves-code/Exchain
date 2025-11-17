<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign JWT tokens. This should be a strong,
    | random string stored in your .env file.
    |
    */
    'secret' => env('JWT_SECRET', 'your-secret-key-here-change-in-production'),

    /*
    |--------------------------------------------------------------------------
    | JWT Token TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid for.
    | Default is 1 week (10080 minutes).
    |
    */
    'ttl' => env('JWT_TTL', 10080),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token can be refreshed.
    | Default is 2 weeks (20160 minutes).
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),
];
