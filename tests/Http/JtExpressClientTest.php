<?php

namespace Laraditz\Courier\JtExpress\Tests\Http;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\Exceptions\CourierException;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class JtExpressClientTest extends TestCase
{
    private function config(): array
    {
        return config('courier.drivers.jtexpress');
    }

    private function successResponse(array $data = []): array
    {
        return [
            'code'      => '1',
            'msg'       => 'success',
            'data'      => $data,
            'requestId' => 'req-123',
        ];
    }

    public function test_dispatch_returns_decoded_envelope_on_success(): void
    {
        Http::fake([
            '*/order/addOrder' => Http::response($this->successResponse(['billCode' => 'BC001']), 200),
        ]);

        $client = new JtExpressClient($this->config());
        $result = $client->dispatch('order/addOrder', ['txlogisticId' => 'REF-001']);

        $this->assertSame('BC001', $result['data']['billCode']);
    }

    public function test_dispatch_sends_correct_headers_and_form_body(): void
    {
        Http::fake([
            '*/order/addOrder' => Http::response($this->successResponse(), 200),
        ]);

        $client = new JtExpressClient($this->config());
        $client->dispatch('order/addOrder', ['txlogisticId' => 'REF-001']);

        Http::assertSent(function ($request) {
            $bizContent = json_decode($request['bizContent'], true);

            return str_contains($request->url(), '/order/addOrder')
                && $request->header('apiAccount')[0] === 'test-api-account'
                && ! empty($request->header('digest')[0])
                && ! empty($request->header('timestamp')[0])
                && $bizContent['txlogisticId'] === 'REF-001'
                && $bizContent['customerCode'] === 'TEST-CUSTOMER-CODE'
                && $bizContent['password'] === strtoupper(md5('test-password'));
        });
    }

    public function test_customer_code_returns_config_value(): void
    {
        $client = new JtExpressClient($this->config());

        $this->assertSame('TEST-CUSTOMER-CODE', $client->customerCode());
    }

    public function test_dispatch_throws_courier_exception_on_http_failure(): void
    {
        Http::fake([
            '*/order/addOrder' => Http::response('Server Error', 500),
        ]);

        $this->expectException(CourierException::class);

        (new JtExpressClient($this->config()))->dispatch('order/addOrder', []);
    }

    public function test_dispatch_throws_courier_exception_on_business_error(): void
    {
        Http::fake([
            '*/order/addOrder' => Http::response([
                'code' => '999001010',
                'msg'  => 'txlogisticId is required',
            ], 200),
        ]);

        $this->expectException(CourierException::class);
        $this->expectExceptionMessageMatches('/txlogisticId is required/');

        (new JtExpressClient($this->config()))->dispatch('order/addOrder', []);
    }
}
