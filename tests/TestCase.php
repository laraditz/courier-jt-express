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

    // courier_api_logs and courier_webhook_logs live in the core package. Without
    // them, ApiLogWriter's catch (Throwable) turns every failed write into a silent
    // Log::error and the suite goes green while logging nothing at all.
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laraditz/courier/database/migrations');
    }

    protected function fixture(string $name): array
    {
        return json_decode(
            file_get_contents(__DIR__."/fixtures/{$name}.json"),
            true
        );
    }
}
