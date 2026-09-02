<?php

return [
    'api_account'   => env('JTEXPRESS_API_ACCOUNT'),
    'private_key'   => env('JTEXPRESS_PRIVATE_KEY'),
    'customer_code' => env('JTEXPRESS_CUSTOMER_CODE'),
    'password'      => env('JTEXPRESS_PASSWORD'),
    // Set true when JTEXPRESS_PASSWORD already holds the encrypted value J&T's
    // console signature tool gives you, rather than the plaintext password.
    'password_encrypted' => env('JTEXPRESS_PASSWORD_ENCRYPTED', false),
    'sandbox'       => env('JTEXPRESS_SANDBOX', false),
    'base_url'      => 'https://ylopenapi.jtexpress.my/webopenplatformapi/api',
    'sandbox_url'   => 'https://demoopenapi.jtexpress.my/webopenplatformapi/api',
    'timeout'       => 30,
];
