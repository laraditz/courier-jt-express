<?php

namespace Laraditz\Courier\JtExpress\Tests;

class ServiceTypeConfigTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function packageConfig(): array
    {
        return require __DIR__.'/../config/jtexpress.php';
    }

    public function test_service_type_map_covers_both_fulfillment_modes(): void
    {
        $config = $this->packageConfig();

        $this->assertArrayHasKey('service_type_map', $config);
        $this->assertArrayHasKey('pickup', $config['service_type_map']);
        $this->assertArrayHasKey('dropoff', $config['service_type_map']);
    }

    /**
     * Per J&T's own Create Order specification: `1 上门取件` is the courier collecting at
     * the door, `6 上门寄件` is a walk-in send. The API rejects anything else outright
     * ("serviceType only support 1 or 6"), so these are the only two values that exist.
     */
    public function test_service_type_map_defaults_match_the_jt_specification(): void
    {
        $config = $this->packageConfig();

        $this->assertSame('1', $config['service_type_map']['pickup']);
        $this->assertSame('6', $config['service_type_map']['dropoff']);
    }

    public function test_service_type_default_is_pickup(): void
    {
        $this->assertSame('1', $this->packageConfig()['service_type_default']);
    }
}
