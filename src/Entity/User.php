<?php

namespace Xjchen\OAuth2\Client\Entity;

use OutOfRangeException;

class User
{
    protected $openid;
    protected $nickname;
    protected $sex;
    protected $province;
    protected $city;
    protected $country;
    protected $headimgUrl;
    protected $privilege;
    protected $unionid;

    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new OutOfRangeException(sprintf(
                '%s does not contain a property by the name of "%s"',
                __CLASS__,
                $name
            ));
        }

        return $this->{$name};
    }

    public function __set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new OutOfRangeException(sprintf(
                '%s does not contain a property by the name of "%s"',
                __CLASS__,
                $property
            ));
        }

        $this->$property = $value;

        return $this;
    }

    public function __isset($name)
    {
        return (property_exists($this, $name));
    }

    public function getArrayCopy()
    {
        return [
            'openid' => $this->openid,
            'nickname' => $this->nickname,
            'sex' => $this->sex,
            'province' => $this->province,
            'city' => $this->city,
            'country' => $this->country,
            'headimgUrl' => $this->headimgUrl,
            'privilege' => $this->privilege,
            'unionid' => $this->unionid,
        ];
    }

    public function exchangeArray(array $data)
    {
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            switch ($key) {
                case 'openid':
                    $this->openid = $value;
                    break;
                case 'nickname':
                    $this->nickname = $value;
                    break;
                case 'sex':
                    $this->sex = $value;
                    break;
                case 'province':
                    $this->province = $value;
                    break;
                case 'city':
                    $this->city = $value;
                    break;
                case 'country':
                    $this->country = $value;
                    break;
                case 'headimgurl':
                    $this->headimgUrl = $value;
                    break;
                case 'privilege':
                    $this->privilege = $value;
                    break;
                case 'unionid':
                    $this->unionid = $value;
                    break;
            }
        }

        return $this;
    }
}
