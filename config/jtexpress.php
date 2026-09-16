<?php

return [
    'api_account'   => env('JTEXPRESS_API_ACCOUNT'),
    'private_key'   => env('JTEXPRESS_PRIVATE_KEY'),
    'customer_code' => env('JTEXPRESS_CUSTOMER_CODE'),
    'password'      => env('JTEXPRESS_PASSWORD'),
    // Set true when JTEXPRESS_PASSWORD already holds the encrypted value J&T's
    // console signature tool gives you, rather than the plaintext password.
    'password_encrypted' => env('JTEXPRESS_PASSWORD_ENCRYPTED', false),
    // Reject a callback whose apiAccount header does not match 'api_account' above.
    // Off by default: confirm the value J&T actually pushes before enabling, or every
    // push will be rejected. See README "Webhook verification".
    'webhook_verify_api_account' => env('JTEXPRESS_WEBHOOK_VERIFY_API_ACCOUNT', false),
    // Seconds of clock skew tolerated on an inbound callback's `timestamp` header
    // before the push is rejected as a replay. Set to 0 to disable the check.
    'webhook_timestamp_tolerance' => env('JTEXPRESS_WEBHOOK_TIMESTAMP_TOLERANCE', 900),
    'sandbox'       => env('JTEXPRESS_SANDBOX', false),
    'base_url'      => 'https://ylopenapi.jtexpress.my/webopenplatformapi/api',
    'sandbox_url'   => 'https://demoopenapi.jtexpress.my/webopenplatformapi/api',
    'timeout'       => 30,
];
