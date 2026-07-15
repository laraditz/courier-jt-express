<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Laraditz\Courier\JtExpress\JtExpressDriver;

class JtExpressServiceProviderTest extends TestCase
{
    public function test_jtexpress_driver_resolves_via_courier_manager(): void
    {
        $driver = app('courier')->driver('jtexpress');

        $this->assertInstanceOf(JtExpressDriver::class, $driver);
    }
}
