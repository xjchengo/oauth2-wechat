<?php

namespace Xjchen\OAuth2\Client\Test\Entity;

use Xjchen\OAuth2\Client\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    private $user;

    private $userArray;

    public function setUp()
    {
        $this->user = new User();

        $this->userArray = [
            'openid' => 'mock_openid',
            'nickname' => 'mock_nickname',
            'sex' => 'mock_sex',
            'province' => 'mock_province',
            'city' => 'mock_city',
            'country' => 'mock_country',
            'headimgUrl' => 'mock_headimgUrl',
            'privilege' => 'mock_privilege',
            'unionid' => 'mock_unionid',
        ];
    }

    public function testExchangeArrayGetArrayCopy()
    {
        $this->user->exchangeArray($this->userArray);
        $this->assertEquals($this->userArray, $this->user->getArrayCopy());
    }

    public function testMagicMethos()
    {
        $this->user->exchangeArray($this->userArray);

        $this->user->nickname = 'mock_change_test';

        $this->assertTrue(isset($this->user->nickname));
        $this->assertEquals('mock_change_test', $this->user->nickname);
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testInvalidMagicSet()
    {
        $this->user->invalidProp = 'mock';
    }

    /**
     * @expectedException \OutOfRangeException
     */
    public function testInvalidMagicGet()
    {
        $this->user->invalidProp;
    }
}
