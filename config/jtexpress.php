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
    // FulfillmentMode -> J&T serviceType. Per J&T's Create Order specification,
    // `1 上门取件` is the courier collecting at the door and `6 上门寄件` is a walk-in
    // send; the API rejects every other value ("serviceType only support 1 or 6").
    // Configurable rather than hardcoded so a contract or account difference is an
    // env change, not a release.
    'service_type_map' => [
        'pickup'  => env('JTEXPRESS_SERVICE_TYPE_PICKUP', '1'),
        'dropoff' => env('JTEXPRESS_SERVICE_TYPE_DROPOFF', '6'),
    ],
    // Used when a ShipmentPayload carries no fulfillment mode, which keeps callers
    // written before FulfillmentMode existed working unchanged.
    'service_type_default' => env('JTEXPRESS_SERVICE_TYPE_DEFAULT', '1'),
    'sandbox'       => env('JTEXPRESS_SANDBOX', false),
    'base_url'      => 'https://ylopenapi.jtexpress.my/webopenplatformapi/api',
    'sandbox_url'   => 'https://demoopenapi.jtexpress.my/webopenplatformapi/api',
    'timeout'       => 30,
];
