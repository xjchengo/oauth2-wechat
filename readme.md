# Wechat OAuth 2.0 Client

This package makes it stupidly simple to integrate your application with Wechat OAuth 2.0 identity provider.


## Installation

Add the following to your `composer.json` file.

> **Note:** Once version 1.0 of the [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client) is released, you'll be able to install from composer without the `@dev` minimum stability flag.

```json
{
    "require": {
        "xjchen/oauth2-wechat": "~0.0"
    }
}
```

And run

``` bash
$ composer update
```


## Usage

### Authorization Code Flow

```php
$provider = new Xjchen\OAuth2\Client\Provider\Wechat([
    'appId'      => 'XXXXXXXXXXX',
    'appSecret'  => 'XXXXXXXXXXX',
    'redirectUri'   => 'http://you-redirect-uri-after-authorize',
    'scope'        => 'snsapi_userinfo',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->state;
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $userDetails = $provider->getUserDetails($token);

        // Use these details to create a new profile
        printf('Hello %s!', $userDetails->nickname);

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    var_dump($token->accessToken);

    // Use this to get a new access token if the old one expires
    var_dump($token->refreshToken);

    // Number of seconds until the access token will expire, and need refreshing
    var_dump($token->expires);
}
```

### Refreshing a Token

```php
$provider = new Xjchen\OAuth2\Client\Provider\Wechat([
    'appId'      => 'XXXXXXXXXXX',
]);

$grant = new \Xjchen\OAuth2\Client\Grant\RefreshToken();
$token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
```


## License

The MIT License (MIT). Please see [License File](https://github.com/xjchengo/oauth2-wechat/blob/master/LICENSE) for more information.
