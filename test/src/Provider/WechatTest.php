<?php

namespace Xjchen\OAuth2\Client\Test\Provider;

use Xjchen\OAuth2\Client\Token\AccessToken;
use Xjchen\OAuth2\Client\Provider\Wechat;
use Ivory\HttpAdapter\Message\Stream\StringStream;
use Mockery as m;

class WechatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new Wechat([
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

    protected function createMockHttpClient()
    {
        $client = m::mock('Ivory\HttpAdapter\HttpAdapterInterface');
        $client->shouldReceive('getConfiguration')->andReturn(new \Ivory\HttpAdapter\Configuration());

        return $client;
    }

    protected function createMockResponse($responseBody)
    {
        $response = m::mock('Ivory\HttpAdapter\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(new StringStream($responseBody));

        return $response;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidGrantString()
    {
        $this->provider->getAccessToken('invalid_grant', ['invalid_parameter' => 'none']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidGrantObject()
    {
        $grant = new \StdClass();
        $this->provider->getAccessToken($grant, ['invalid_parameter' => 'none']);
    }

    public function testAuthorizationUrlStateParam()
    {
        $this->assertContains('state=XXX', $this->provider->getAuthorizationUrl([
            'state' => 'XXX'
        ]));
    }

    /**
     * Tests https://github.com/thephpXjchen/oauth2-client/issues/134
     */
    public function testConstructorSetsProperties()
    {
        $options = [
            'appId' => '1234',
            'appSecret' => '4567',
            'redirectUri' => 'http://example.org/redirect',
            'state' => 'foo',
            'scope' => 'bar',
        ];

        $mockProvider = new Wechat($options);

        foreach ($options as $key => $value) {
            $this->assertEquals($value, $mockProvider->{$key});
        }
    }

    public function testConstructorSetsHttpAdapter()
    {
        $mockAdapter = m::mock('Ivory\HttpAdapter\HttpAdapterInterface');

        $mockProvider = new Wechat([], $mockAdapter);
        $this->assertSame($mockAdapter, $mockProvider->getHttpClient());
    }

    public function testSetRedirectHandler()
    {
        $this->testFunction = false;

        $callback = function ($url) {
            $this->testFunction = $url;
        };

        $this->provider->setRedirectHandler($callback);

        $this->provider->authorize('http://test.url/');

        $this->assertNotFalse($this->testFunction);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('appid', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertNotNull($this->provider->state);
    }

    public function testUrlAccessToken()
    {
        $url = $this->provider->urlAccessToken();
        $uri = parse_url($url);

        $this->assertEquals('/sns/oauth2/access_token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $client = $this->createMockHttpClient();
        $client->shouldReceive('get')->times(1)->andReturn($this->createMockResponse('{"access_token":"ACCESS_TOKEN","expires_in":7200,"refresh_token":"REFRESH_TOKEN","openid":"OPENID","scope":"SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}'));

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('ACCESS_TOKEN', $token->accessToken);
        $this->assertLessThanOrEqual(time() + 7200, $token->expires);
        $this->assertGreaterThanOrEqual(time(), $token->expires);
        $this->assertEquals('REFRESH_TOKEN', $token->refreshToken);
        $this->assertEquals('OPENID', $token->openid);
    }

    /**
     * @ticket 230
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Required option not passed: access_token
     */
    public function testGetAccessTokenWithInvalidJson()
    {
        $client = $this->createMockHttpClient();
        $response = $this->createMockResponse('invalid');

        $client->shouldReceive('get')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testScopes()
    {
        $this->provider->setScope('snsapi_base');
        $this->assertEquals('snsapi_base', $this->provider->getScope());
    }

    public function testUserData()
    {
        $client = $this->createMockHttpClient();

        $getAccessTokenResponse = $this->createMockResponse('{"access_token":"ACCESS_TOKEN","expires_in":7200,"refresh_token":"REFRESH_TOKEN","openid":"OPENID","scope":"SCOPE","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}');
        $getUserInfoResponse = $this->createMockResponse('{"openid":"OPENID","nickname":"NICKNAME","sex":"1","province":"PROVINCE","city":"CITY","country":"COUNTRY","headimgurl":"http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46","unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"}');

        $client->shouldReceive('get')->times(2)->andReturn($getAccessTokenResponse, $getUserInfoResponse);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getUserDetails($token);

        $this->assertEquals('NICKNAME', $user->nickname);
    }
}
