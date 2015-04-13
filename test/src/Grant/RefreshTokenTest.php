<?php

namespace League\OAuth2\Client\Test\Grant;

use Mockery as m;

class RefreshTokenTest extends \PHPUnit_Framework_TestCase
{
    /** @var \League\OAuth2\Client\Provider\AbstractProvider */
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
        $response = m::mock('Ivory\HttpAdapter\Message\ResponseInterface');
        $response->shouldReceive('getBody')->times(2)->andReturn(new \Ivory\HttpAdapter\Message\Stream\StringStream('{"access_token":"ACCESS_TOKEN","expires_in":7200,"refresh_token":"REFRESH_TOKEN","openid":"OPENID","scope":"SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}'));

        $client = m::mock('Ivory\HttpAdapter\HttpAdapterInterface');
        $client->shouldReceive('get')->times(2)->andReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertInstanceOf('Xjchen\OAuth2\Client\Token\AccessToken', $token);

        $grant = new \Xjchen\OAuth2\Client\Grant\RefreshToken();
        $this->assertEquals('refresh_token', (string) $grant);

        $newToken = $this->provider->getAccessToken($grant, ['refresh_token' => $token->refreshToken]);
        $this->assertInstanceOf('Xjchen\OAuth2\Client\Token\AccessToken', $newToken);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testInvalidRefreshToken()
    {
        $response = m::mock('Ivory\HttpAdapter\Message\ResponseInterface');
        $response->shouldReceive('getBody')->times(1)->andReturn(new \Ivory\HttpAdapter\Message\Stream\StringStream('{"access_token":"ACCESS_TOKEN","expires_in":7200,"refresh_token":"REFRESH_TOKEN","openid":"OPENID","scope":"SCOPE"}'));

        $client = m::mock('Ivory\HttpAdapter\HttpAdapterInterface');
        $client->shouldReceive('get')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $grant = new \Xjchen\OAuth2\Client\Grant\RefreshToken();
        $refreshToken = $this->provider->getAccessToken($grant, ['invalid_refresh_token' => $token->refreshToken]);
    }
}
