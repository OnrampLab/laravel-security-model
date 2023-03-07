<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hash Key
    |--------------------------------------------------------------------------
    |
    | This key is used for generating hash value of blind index.
    |
    */

    'hash_key' => env('SECURITY_MODEL_HASH_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Key Provider Name
    |--------------------------------------------------------------------------
    |
    | Here you may define a default provider.
    |
    */

    'default' => env('SECURITY_MODEL_KEY_PROVIDER', 'aws_kms'),

    /*
    |--------------------------------------------------------------------------
    | Key Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the provider information for each service that
    | is used by your application.
    |
    | Drivers: "aws_kms"
    |
    */

    'providers' => [
        'aws_kms' => [
            'driver' => 'aws_kms',
            'access_key' => env('AWS_ACCESS_KEY_ID'),
            'access_secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'key_id' => env('AWS_KMS_KEY_ID'),
        ],
    ],
];
