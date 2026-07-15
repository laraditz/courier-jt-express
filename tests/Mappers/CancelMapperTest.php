<?php

namespace Laraditz\Courier\JtExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\JtExpress\Mappers\CancelMapper;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class CancelMapperTest extends TestCase
{
    public function test_map_returns_successful_cancel_result(): void
    {
        $envelope = $this->fixture('cancel-success');

        $result = CancelMapper::map($envelope);

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('success', $result->message);
        $this->assertSame('630002563505', $result->meta()['bill_code']);
        $this->assertSame('YLTEST202404101520', $result->meta()['txlogistic_id']);
    }
}
