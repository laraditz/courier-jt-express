<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Laraditz\Courier\CourierServiceProvider;
use Laraditz\Courier\JtExpress\JtExpressServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CourierServiceProvider::class,
            JtExpressServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('courier.drivers.jtexpress', [
            'api_account'   => 'test-api-account',
            'private_key'   => 'test-private-key',
            'customer_code' => 'TEST-CUSTOMER-CODE',
            'password'      => 'test-password',
            'sandbox'       => true,
            'sandbox_url'   => 'https://demoopenapi.jtexpress.my/webopenplatformapi/api',
            'base_url'      => 'https://ylopenapi.jtexpress.my/webopenplatformapi/api',
            'timeout'       => 30,
        ]);
    }

    protected function fixture(string $name): array
    {
        return json_decode(
            file_get_contents(__DIR__."/fixtures/{$name}.json"),
            true
        );
    }
}
