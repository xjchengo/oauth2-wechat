<?php

namespace Xjchen\OAuth2\Client\Provider;

use Closure;
use InvalidArgumentException;
use Xjchen\OAuth2\Client\Entity\User;
use Xjchen\OAuth2\Client\Token\AccessToken;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Ivory\HttpAdapter\HttpAdapterException;
use Ivory\HttpAdapter\HttpAdapterInterface;
use Xjchen\OAuth2\Client\Grant\GrantInterface;
use Xjchen\OAuth2\Client\Exception\IDPException;

class Wechat
{
    public $appId;

    public $appSecret;

    public $scope;

    public $redirectUri = '';

    public $state;

    public $lang = 'zh_CN';

    /**
     * @var HttpAdapterInterface
     */
    protected $httpClient;

    protected $redirectHandler;

    /**
     * @var int This represents: PHP_QUERY_RFC1738, which is the default value for php 5.4
     *          and the default encryption type for the http_build_query setup
     */
    protected $httpBuildEncType = 1;

    public function __construct($options = [], HttpAdapterInterface $httpClient = null)
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        $this->setHttpClient($httpClient ?: new CurlHttpAdapter());
    }

    public function setHttpClient(HttpAdapterInterface $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    public function getHttpClient()
    {
        $client = $this->httpClient;

        return $client;
    }

    /**
     * Get the URL that this provider uses to begin authorization.
     *
     * @return string
     */
    public function urlAuthorize()
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize';
    }

    /**
     * Get the URL that this provider users to request an access token.
     *
     * @return string
     */
    public function urlAccessToken()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * Get the URL that this provider users to refresh an access token.
     *
     * @return string
     */
    public function urlRefreshAccessToken()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
    }

    /**
     * Get the URL that this provider uses to request user details.
     *
     * Since this URL is typically an authorized route, most providers will require you to pass the access_token as
     * a parameter to the request. For example, the google url is:
     *
     * 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$token
     *
     * @param AccessToken $token
     * @return string
     */
    public function urlUserDetails(AccessToken $token)
    {
        $params = [
            'access_token' => $token->accessToken,
            'openid' => $token->openid,
            'lang' => $this->lang
        ];
        return 'https://api.weixin.qq.com/sns/userinfo' . '?' . $this->httpBuildQuery($params, '', '&');
    }

    /**
     * Given an object response from the server, process the user details into a format expected by the user
     * of the client.
     *
     * @param object $response
     * @param AccessToken $token
     * @return mixed
     */
    public function userDetails($response, AccessToken $token)
    {
        $user = new User();

        $privilege = (isset($response->privilege)) ? $response->privilege : null;
        $unionid = (isset($response->unionid)) ? $response->unionid : null;

        $user->exchangeArray([
            'openid' => $response->openid,
            'nickname' => $response->nickname,
            'sex' => $response->sex,
            'province' => $response->province,
            'city' => $response->city,
            'country' => $response->country,
            'headimgUrl' => $response->headimgurl,
            'privilege' => $privilege,
            'unionid' => $unionid,
        ]);

        return $user;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getAuthorizationUrl($options = [])
    {
        $this->state = isset($options['state']) ? $options['state'] : md5(uniqid(rand(), true));

        $params = [
            'appid' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => isset($options['response_type']) ? $options['response_type'] : 'code',
            'scope' => $this->scope,
            'state' => $this->state,
        ];

        return $this->urlAuthorize() . '?' . $this->httpBuildQuery($params, '', '&') . '#wechat_redirect';
    }

    public function authorize($options = [])
    {
        $url = $this->getAuthorizationUrl($options);
        if ($this->redirectHandler) {
            $handler = $this->redirectHandler;
            return $handler($url);
        }
        header('Location: ' . $url);
        exit;
    }

    public function getAccessToken($grant = 'authorization_code', $params = [])
    {
        if ($grant == 'authorization_code') {
            $defaultParams = [
                'appid'     => $this->appId,
                'secret' => $this->appSecret,
                'grant_type'    => $grant,
            ];
        } elseif ($grant == 'refresh_token') {
            $defaultParams = [
                'appid'     => $this->appId,
                'grant_type'    => $grant,
            ];
        } else {
            throw new InvalidArgumentException('Wechat does not support this grant type');
        }

        if (is_string($grant)) {
            // PascalCase the grant. E.g: 'authorization_code' becomes 'AuthorizationCode'
            $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $grant)));
            $grant = 'Xjchen\\OAuth2\\Client\\Grant\\'.$className;
            if (! class_exists($grant)) {
                throw new InvalidArgumentException('Unknown grant "'.$grant.'"');
            }
            $grant = new $grant();
        } elseif (! $grant instanceof GrantInterface) {
            $message = get_class($grant).' is not an instance of League\OAuth2\Client\Grant\GrantInterface';
            throw new InvalidArgumentException($message);
        }

        $requestParams = $grant->prepRequestParams($defaultParams, $params);

        try {
            $client = $this->getHttpClient();
            if ($grant == 'authorization_code') {
                $url = $this->urlAccessToken();
            } else {
                $url = $this->urlRefreshAccessToken();
            }
            $httpResponse = $client->get(
                $url . '?' . $this->httpBuildQuery($requestParams, '', '&')
            );
            $response = (string) $httpResponse->getBody();
        } catch (HttpAdapterException $e) {
            $response = (string) $e->getResponse()->getBody();
        }

        $result = json_decode($response, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $result = [];
        }

        if (isset($result['errcode']) && ! empty($result['errcode'])) {
            // @codeCoverageIgnoreStart
            throw new IDPException($result);
            // @codeCoverageIgnoreEnd
        }

        return $grant->handleResponse($result);
    }

    public function getUserDetails(AccessToken $token)
    {
        $response = $this->fetchUserDetails($token);

        return $this->userDetails(json_decode($response), $token);
    }

    /**
     * Build HTTP the HTTP query, handling PHP version control options
     *
     * @param  array        $params
     * @param  integer      $numeric_prefix
     * @param  string       $arg_separator
     * @param  null|integer $enc_type
     *
     * @return string
     * @codeCoverageIgnoreStart
     */
    protected function httpBuildQuery($params, $numeric_prefix = 0, $arg_separator = '&', $enc_type = null)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !defined('HHVM_VERSION')) {
            if ($enc_type === null) {
                $enc_type = $this->httpBuildEncType;
            }
            $url = http_build_query($params, $numeric_prefix, $arg_separator, $enc_type);
        } else {
            $url = http_build_query($params, $numeric_prefix, $arg_separator);
        }

        return $url;
    }

    protected function fetchUserDetails(AccessToken $token)
    {
        $url = $this->urlUserDetails($token);

        return $this->fetchProviderData($url);
    }

    protected function fetchProviderData($url)
    {
        try {
            $client = $this->getHttpClient();

            $httpResponse = $client->get($url);

            $response = (string) $httpResponse->getBody();
        } catch (HttpAdapterException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", (string) $e->getResponse()->getBody());
            throw new IDPException(end($raw_response));
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    public function setRedirectHandler(Closure $handler)
    {
        $this->redirectHandler = $handler;
    }
}
