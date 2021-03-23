<?php

namespace Nick\Signature\Api\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\CoroutineHandler;
use Nick\Signature\Api\Util\Hmac;

class RequestClient
{
    private $_algos = [ 'md5', 'sha1', 'sha128', 'sha256' ];
    private $_config = [];
    private $_headers = [];
    private $_params = [];
    private $_signature_params = [];

    private $_client;
    private $_response;
    private $_response_code;
    private $_response_headers;
    private $_response_content;

    private $_url;
    private $_key;
    private $_secret;
    private $_token_before_signature;
    private $_signature;
    private $_base_url;
    private $_is_secret_backup_enable;
    private $_path;
    private $_method;

    /**
     * 单例
     *
     * @var RequestClient
     */
    private static $_instance;

    /**
     * 单例
     *
     * @return RequestClient
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
        if (count($config) > 0) {
            
            $this->_config = array_merge($this->_config, $config);

            if (isset($config['key'])) {
                $this->_key = $config['key'];
            }
            
            if (isset($config['secret'])) {
                $this->_secret = $config['secret'];
            }
            
            if (isset($config['base_url'])) {
                $this->_base_url = $config['base_url'];
            }

            if (isset($config['secret_backup_enable'])) {
                // 判断当前密钥是否是在使用备用的密钥
                $this->_is_secret_backup_enable = boolval($config['secret_backup_enable']);
            }
            
            // 请求头部
            if (isset($this->_config['headers']) && is_array($this->_config['headers'])) {
                $this->_headers = array_merge($this->_headers, $this->_config['headers']);
            }
        }

        return $this;
    }

    /**
     * 发送请求数据
     *
     * @param string $method
     * @param string $path
     * @param array $param
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(string $method, string $path, array $params)
    {
        $this->_response = null;
        $this->_response_content = null;
        $this->_response_code = 0;
        $this->_method = strtolower($method);
        $this->_url = sprintf('%s/%s', trim($this->_base_url, '/'), trim($path, '/'));

        // 解析获得完成的路径PATH和QUERY参数
        $arr = parse_url($this->_url);

        if (isset($arr['path'])) {
            $this->_path = $arr['path'];
        } else {
            $this->_path = '/';
        }
    
        if (isset($arr['query'])) {
            parse_str($arr['query'], $firstPartParams);
            $this->_params = array_merge($firstPartParams, $params);
        } else {
            $this->_params = $params;
        }
        
        $this->_headers['x-app-key'] = $this->_key;
        $this->_headers['x-app-signature'] = $this->createSignature();
        if ($this->_is_secret_backup_enable) {
            // 判断当前密钥是否是在使用备用的密钥
            $this->_headers['x-app-backup-secret-enable'] = 1;
        }

        if ($this->_method == 'get') {
            $options = [
                'headers' => $this->_headers,
                'query' => $this->_params,
            ];
        } else {
            $options = [
                'headers' => $this->_headers,
                'form_params' => $this->_params
            ];
        }

        $this->_response = $this->getClient()->{$method}($this->_url, $options);
        $this->_response_code = $this->_response->getStatusCode();
        $this->_response_headers = $this->_response->getHeaders();
        if ($this->_response_code == 200) {
            $this->_response_content = $this->_response->getBody()->getContents();
        }

        return $this;
    }

    /**
     * 取得当前请求参数
     * @return array
     */
    public function getSignatureParams()
    {
        return $this->_signature_params;
    }

    /**
     * 获得当前请求的URL
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->_url;
    }

    /**
     * 取得当前请求参数
     * @return array
     */
    public function getRequestParams()
    {
        return $this->_params;
    }

    /**
     * 获得当前请求的响应实例
     * @return object
     */
    public function getRequestHeaders()
    {
        return $this->_headers;
    }

    /**
     * 获得当前请求的响应头部
     * @return object
     */
    public function getResponseHeaders()
    {
        return $this->_response_headers;
    }

    /**
     * 获得当前请求的响应实例
     * @return integer
     */
    public function getResponseCode()
    {
        if (!$this->_response_code) {

            if ($this->_response) {
                $this->_response_code = $this->_response->getStatusCode();
            }
        }
        return $this->_response_code;
    }

    /**
     * 获得当前请求的返回内容
     * @return string
     */
    public function getResponseContent()
    {
        if (!$this->_response_content) {

            if ($this->_response) {
                $this->_response_content = $this->_response->getBody()->getContents();
            }
        }
        return $this->_response_content;
    }

    /**
     * 获得当前请求的响应实例
     * @return object
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * 密钥和参数签名
     *
     * @return string
     */
    private function createSignature()
    {
        $this->_signature_params = $this->_params;
        $this->_signature_params['api_method'] = $this->_method;

        if (!isset($this->_config['algo']) || !in_array($this->_config['algo'], $this->_algos)) {
            $this->_config['algo'] = 'sha1';
        }

        $this->_headers['x-app-algo'] = $this->_config['algo'];
        $this->_headers['x-app-start-at'] = time();
        if (isset($this->_config['experied']) && $this->_config['experied'] > 0) {
            $this->_headers['x-app-experied-at']  = $this->_headers['x-app-start-at'] + $this->_config['experied'];
            $this->_signature_params['api_experied_at'] = $this->_headers['x-app-experied-at'] ;
        }
        
        ksort($this->_signature_params);

        $this->_token_before_signature = strtolower(http_build_query($this->_signature_params));
        $this->_signature = Hmac::signature($this->_config['algo'], $this->_secret, $this->_token_before_signature);

        return $this->_signature;
    }

    /**
     * 获取客户端
     * @return \GuzzleHttp\Client
     */
    private function getClient()
    {
        if (!$this->_client) {
            $this->_client = make(Client::class, [
                'config' => [
                    'handler' => HandlerStack::create(new CoroutineHandler()),
                    'timeout' => 5,
                    'swoole' => [
                        'timeout' => 2,
                        'socket_buffer_size' => 1024 * 1024 * 2,
                    ],
                ],
            ]);
        }

        return $this->_client;
    }
}
