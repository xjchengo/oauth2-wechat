<?php

namespace Xjchen\OAuth2\Client\Grant;

use BadMethodCallException;
use Xjchen\OAuth2\Client\Grant\GrantInterface;
use Xjchen\OAuth2\Client\Token\AccessToken;

class RefreshToken implements GrantInterface
{
    public function __toString()
    {
        return 'refresh_token';
    }

    public function prepRequestParams($defaultParams, $params)
    {
        if (! isset($params['refresh_token']) || empty($params['refresh_token'])) {
            throw new BadMethodCallException('Missing refresh_token');
        }

        $params['grant_type'] = 'refresh_token';

        return array_merge($defaultParams, $params);
    }

    public function handleResponse($response = [])
    {
        return new AccessToken($response);
    }
}
