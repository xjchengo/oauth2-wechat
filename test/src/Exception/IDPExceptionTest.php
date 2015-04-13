<?php

namespace Xjchen\OAuth2\Client\Test\Exception;

use Xjchen\OAuth2\Client\Exception\IDPException;

class IDPExceptionTest extends \PHPUnit_Framework_TestCase
{
    private $error;

    public function setUp()
    {
        $this->error = [
            'errcode' => '40003',
            'errmsg' => 'invalid openid'
        ];
    }

    public function testGetTypeMessage()
    {
        $exception = new IDPException($this->error);

        $this->assertEquals('Wechat Interface Exception', $exception->getType());
    }

    public function testGetTypeEmpty()
    {
        $exception = new IDPException([]);

        $this->assertEquals('Wechat Interface Exception', $exception->getType());
    }

    public function testAsString()
    {
        $exception = new IDPException($this->error);

        $this->assertEquals('Wechat Interface Exception: 40003: invalid openid', (string)$exception);
    }

    public function testGetResponseBody()
    {
        $exception = new IDPException($this->error);

        $this->assertEquals(
            $this->error,
            $exception->getResponseBody()
        );
    }

}
