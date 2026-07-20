<?php

namespace Laraditz\Courier\JtExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\Exceptions\LabelFetchException;
use Laraditz\Courier\JtExpress\Mappers\LabelMapper;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class LabelMapperTest extends TestCase
{
    public function test_map_returns_pdf_label_when_base64_content_present(): void
    {
        $envelope = $this->fixture('get-label-success-base64');

        $result = LabelMapper::map($envelope['data'], '670300032350');

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('pdf', $result->format);
        $this->assertSame('JVBERi1mYWtl', $result->content);
    }

    public function test_map_returns_url_label_when_base64_content_empty(): void
    {
        $envelope = $this->fixture('get-label-success-url');

        $result = LabelMapper::map($envelope['data'], '670300032350');

        $this->assertSame('url', $result->format);
        $this->assertSame('https://ylopenapi.jtexpress.my/webopenplatformapi/api/pic/file?url=fake.pdf', $result->content);
    }

    public function test_map_throws_when_both_base64_and_url_missing(): void
    {
        $this->expectException(LabelFetchException::class);

        LabelMapper::map(['base64EncodeContent' => '', 'urlContent' => ''], '670300032350');
    }
}
