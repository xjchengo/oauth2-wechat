<?php

namespace Xjchen\OAuth2\Client\Exception;

use Exception;

class IDPException extends Exception
{
    protected $result;

    public function __construct($result)
    {
        $this->result = $result;

        $code = isset($result['errcode']) ? $result['errcode'] : 0;

        if (isset($result['errmsg']) && $result['errmsg'] !== '') {
            $message = $result['errmsg'];
        } else {
            $message = 'Unknown Error.';
        }

        parent::__construct($message, $code);
    }

    public function getResponseBody()
    {
        return $this->result;
    }

    public function getType()
    {
        $result = 'Wechat Interface Exception';

        return $result;
    }

    /**
     * To make debugging easier.
     *
     * @return string The string representation of the error.
     */
    public function __toString()
    {
        $str = $this->getType().': ';

        if ($this->code != 0) {
            $str .= $this->code.': ';
        }

        return $str.$this->message;
    }
}
