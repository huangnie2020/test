<?php

namespace Nick\Signature\Api\Util;

use Nick\Signature\Api\Exception\RequestSignatureException;

class Hmac
{
    /**
     * 密钥签名方法
     *
     * @param string $algo      加密算法，例如 md5 sha1 sha128 sha256
     * @param string $secret    私钥
     * @param string $token     明文
     * @return string
     * @throws RequestSignatureException
     */
    public static function signature(string $algo, string $secret, string $token)
    {
        if ($algo == 'md5') {
            return md5($secret . $token);
        } else if (function_exists('hash_hmac')) {
            $signature = bin2hex(hash_hmac($algo, $token, $secret, true));
        } else {
            throw new RequestSignatureException('hash_hmac 不存在，请你选用md5算法');
        }
        return $signature;
    }
}