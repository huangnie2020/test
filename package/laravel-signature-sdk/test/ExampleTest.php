<?php

use Nick\Signature\Api\Facade\RequestFacade;
use Nick\Signature\Api\Http\RequestProxy;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * base_url 改为实际的测试地址
     *
     * @var array
     */
    private $config = array(
        'enable' => true,                       //是否可用
        'name' => '测试',                        //您的应用名称
        'key' => 'test-key',                    //您的应用的key
        'secret' => 'test-secret',              //您的应用的secret
        'secret_backup' => 'test-secret-2',     //您的应用的备用secret，用于更新配置时使用
        'secret_backup_enable' => true,         //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
        'algo' => 'sha256',                     //缺省时，默认为sha1
        'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
        'base_url' => 'http://127.0.0.1:9501',  //api地址host, 例如： http://127.0.0.1:9501
    );

    public function testRecommendByUid()
    {
        // $proxy = (new RequestProxy);
        // $proxy->setConfig($this->config);
        // $result = $proxy->get('api/product-recommend-by-uid/ydj014279430', ['refresh' => 0]);

        // print_r([

        //     'result_code' => $result->getCode(),
        //     'result_message' => $result->getMessage(),
        //     'result_data' => $result->getData(),

        //     'request_headers' => $result->getRequestHeaders(),
        //     'request_url' => $result->getRequestUrl(),

        //     'response_code' => $result->getResponseCode(),
        //     'response_content' => $result->getResponseContent(),
        // ]);


        $this->assertTrue(true);
    }

    public function testWebhook()
    {
        // $proxy = (new RequestProxy);
        // $proxy->setConfig($this->config);
        // $result = $proxy->get('api/webhook-demo', ['title' => 'mmmmmmmmmm']);

        // print_r([
        
        //     'result_code' => $result->getCode(),
        //     'result_message' => $result->getMessage(),
        //     'result_data' => $result->getData(),

        //     'request_params' => $p->getParams(),
        //     'request_path' => $p->getPath(),
        //     'request_headers' => $result->getRequestHeaders(),
        //     'request_url' => $result->getRequestUrl(),

        //     'response_code' => $result->getResponseCode(),
        //     'response_content' => $result->getResponseContent(),
        // ]);

        $this->assertTrue(true);
    }

    public function testApiServer()
    {
        $proxy = (new RequestProxy);
        // 或者
        $proxy = RequestProxy::getInstance();
        $proxy->setConfig([
            'enable' => true,                       //是否可用
            'name' => '测试',                        //您的应用名称
            'key' => 'test-key',                    //您的应用的key
            'secret' => 'test-secret',              //您的应用的secret
            'secret_backup' => 'test-secret-1',     //您的应用的备用secret，用于更新配置时使用
            'secret_backup_enable' => true,        //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
            'algo' => 'sha256',                     //缺省时，默认为sha1
            'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
            'base_url' => 'http://127.0.0.1:8000',  //api地址host, 例如： http://127.0.0.1:9501
        ]);
        $result = $proxy->get('api/demo', ['refresh' => 0]);

        print_r([

            'result_code' => $result->getCode(),
            'result_message' => $result->getMessage(),
            'result_data' => $result->getData(),
            
            'request_url' => $result->getRequestUrl(),
            'request_headers' => $result->getRequestHeaders(),
            'request_signature_params' => $result->getRequestSignatureParams(),

            'response_code' => $result->getResponseCode(),
            // 'response_headers' => $result->getResponseHeaders(),
            'response_content' => $result->getResponseContent(),
        ]);


        $result = $proxy->post('api/demo', ['refresh' => 0]);

        print_r([

            'result_code' => $result->getCode(),
            'result_message' => $result->getMessage(),
            'result_data' => $result->getData(),

            'request_url' => $result->getRequestUrl(),
            'request_headers' => $result->getRequestHeaders(),
            'request_signature_params' => $result->getRequestSignatureParams(),

            'response_code' => $result->getResponseCode(),
            // 'response_headers' => $result->getResponseHeaders(),
            'response_content' => $result->getResponseContent(),
        ]);

        $this->assertTrue(true);
    }
}
