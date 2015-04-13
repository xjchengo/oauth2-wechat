<?php

namespace Xjchen\OAuth2\Client\Test\Grant;

use Mockery as m;

class AuthorizationCodeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \League\OAuth2\Client\Provider\Wechat */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Xjchen\OAuth2\Client\Provider\Wechat([
            'appId'      => 'wx3ecb054c0ca8d702',
            'appSecret'  => 'e1e2c3881441974f6d3377247a754e8ax',
            'redirectUri'   => 'http://newvoice.echo58.com/demo.php',
            'scope'        => 'snsapi_userinfo',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testGetAccessToken()
    {
        $grant = new \Xjchen\OAuth2\Client\Grant\AuthorizationCode();
        $this->assertEquals('authorization_code', (string) $grant);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testInvalidRefreshToken()
    {
        $this->provider->getAccessToken('authorization_code', ['invalid_code' => 'mock_authorization_code']);
    }
}
