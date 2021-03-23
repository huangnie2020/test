<?php

namespace Nick\Signature\Api\Http;

use Nick\Signature\Api\Exception\RequestParamException;
use Nick\Signature\Api\Exception\RequestResultException;

class RequestProxy
{
    /**
     * 接口密钥
     * [
     *    "key" => "客户端私钥",
     *    "secret" => "客户端公钥",
     *    "algo" => "签名算法", //缺省时的默认值 sha1, 例如 md5, sha1, sha128, sha256 之一
     *    "expired" => -1,     //签名有效时长，单位：秒，负数或零表示无限制
     *    "base_url" => "服务端HOST, 例如 http://127.0.0.1"
     * ]
     *
     * @var array
     */
    private $config = [];
    private $request;
    
    private $code;
    private $data;
    private $message;

    /**
     * 单例
     *
     * @var RequestProxy
     */
    private static $_instance;

    /**
     * 单例
     *
     * @return RequestProxy
     */
    public static function getInstance()
    {
        // 初始化网络客户端
        if (!self::$_instance) {
            self::$_instance = new static;
        }

        return self::$_instance;
    }

    /**
     * 设置配置参数
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        if (isset($config['enable']) && !$config['enable']) {
            throw new RequestParamException('应用已被禁用');
        }

        if (!isset($config['key']) || !$config['key']) {
            throw new RequestParamException('配置参数-客户端私钥(key) 不能为空');
        }

        // 可以启用备用secret，以便于更新secret
        if (isset($config['secret_backup_enable']) && $config['secret_backup_enable'] && isset($config['secret_backup']) && $config['secret_backup']) {
            $config['secret'] = $config['secret_backup'];
            $config['secret_backup_enable'] = $config['secret_backup_enable'];
        } else if (!isset($config['secret']) || !$config['secret']) {
            throw new RequestParamException('配置参数-客户端公钥(secret) 不能为空');
        }
        
        if (!isset($config['base_url']) || !$config['base_url']) {
            throw new RequestParamException('配置参数-服务端HOST(base_url) 不能为空');
        }

        if (isset($config['experied'])) {
            if (!is_numeric($config['experied'])) {
                throw new RequestParamException('配置参数-有效时间长度[单位：秒](experied) 必须为数字');
            }
            $config['experied'] = intval($config['experied']);
        } else {
            $config['experied'] = -1;
        }

        if (isset($config['experied'])) {
            if (!is_numeric($config['experied'])) {
                throw new RequestParamException('配置参数-有效时间长度[单位：秒](experied) 必须为数字');
            }
            $config['experied'] = intval($config['experied']);
        } else {
            $config['experied'] = -1;
        }
    
        $this->config = $config;

        return $this;
    }

    /**
     * 获得当前请求结果的code字段
     * @return integer
     */
    public function getCode()
    {
        if ($this->code < 1) {
            $this->parseResult();
        }
        return $this->code;
    }

    /**
     * 获得当前请求结果的data字段
     * @return mixed
     */
    public function getData()
    {
        if ($this->code < 1) {
            $this->parseResult();
        }
        return $this->data;
    }

    /**
     * 获得当前请求结果的message字段
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 用GET方式请求
     * @param string $path
     * @param array $param
     * @param array $config
     * @return $this
     */
    public function get(string $path, array $params, array $config=[])
    {
        $this->clearResult();
        if (count($config) == 0) {
            $config = $this->config;
        }
        $this->request = RequestClient::getInstance()->setConfig($config)->send('get', $path, $params);
        return $this;
    }

    /**
     * 用POST方式请求
     * @param string $path
     * @param array $param
     * @param array $config
     * @return $this
     */
    public function post(string $path, array $params, array $config=[])
    {
        $this->clearResult();
        if (count($config) == 0) {
            $config = $this->config;
        }
        $this->request = RequestClient::getInstance()->setConfig($config)->send('post', $path, $params);
        return $this;
    }

    /**
     * @param string $path
     * @param array $param
     * @param array $config
     * @return $this
     */
    public function put(string $path, array $params, array $config=[])
    {
        $this->clearResult();
        if (count($config) == 0) {
            $config = $this->config;
        }
        $this->request = RequestClient::getInstance()->setConfig($config)->send('put', $path, $params);
        return $this;
    }

    /**
     * @param string $path
     * @param array $param
     * @param array $config
     * @return $this
     */
    public function delete(string $path, array $params, array $config=[])
    {        
        $this->clearResult();
        if (count($config) == 0) {
            $config = $this->config;
        }
        $this->request = RequestClient::getInstance()->setConfig($config)->send('delete', $path, $params);
        return $this;
    }

    /**
     * 获得当前请求的网址URL
     * @return string
     */
    public function getRequestUrl()
    {
        return RequestClient::getInstance()->getRequestUrl();
    }

    /**
     * 获得当前请求的头部
     * @return string
     */
    public function getRequestHeaders()
    {
        return RequestClient::getInstance()->getRequestHeaders();
    }
    
    /**
     * 获得当前请求的参数
     * @return string
     */
    public function getRequestParams()
    {
        return RequestClient::getInstance()->getRequestParams();
    }
    
    /**
     * 获得当前请求的签名参数
     * @return string
     */
    public function getRequestSignatureParams()
    {
        return RequestClient::getInstance()->getSignatureParams();
    }

    /**
     * 获得当前请求的响应状态码
     * @return integer
     */
    public function getResponseCode()
    {
        return RequestClient::getInstance()->getResponseCode();
    }

    /**
     * 获得当前请求的的响应头部
     * @return string
     */
    public function getResponseHeaders()
    {
        return RequestClient::getInstance()->getResponseHeaders();
    }

    /**
     * 获得当前请求的响应内容(http response body content)
     * @return string
     */
    public function getResponseContent()
    {
        return RequestClient::getInstance()->getResponseContent();
    }

    /**
     * 解析当前请求结果
     *
     * @return true
     */
    private function parseResult()
    {
        if ($this->request) {
            try {
                $resultStr = $this->request->getResponseContent();
                $resultJson = json_decode($resultStr, true);
                if (isset($resultJson['code']) && isset($resultJson['data'])) {
                    $this->message = $resultJson['message'] ?? '';
                    $this->code = $resultJson['code'];
                    $this->data = $resultJson['data'];
                } else {
                    $this->message = 'response content is not standard json struct, you need parse by youself.';
                    $this->code = 1;
                    $this->data = $resultStr;
                }
            } catch (\Exception $e) {
                $this->code = $e->getCode();
                $this->message = $e->getMessage();
            }
        }

        if ($this->code != 200) {
            if ($this->code == -1 && !$this->message) {
                $this->message = 'Request Failure';
            }
        }

        return true;
    }

    /**
     * 清空请求结果
     *
     * @return true
     */
    private function clearResult()
    {
        $this->mesage = '';
        $this->code = -1;
        $this->data = [];

        return true;
    }
}