# Hyperf Signature Sdk

兼容>=PHP7.2版本

## 安装

1. 在composer.json文件的`repositories`中添加仓库地址

```php
// 添加 path 类型源
"repositories": [
        {
            "type": "path",
            "url": "../hyperf-signature"
        }
]
```


2. 配置

```php
// config/autoload/app-sign.php
<?php
declare(strict_types=1);

return [
    'default' => env('APP_KEY_DEFAULT', 'test-key'),
    'app' => [
        'test-key' => [
            'enable' => true,                       //是否可用
            'name' => '测试',                        //您的应用名称
            'key' => 'test-key',                    //您的应用的key
            'secret' => 'test-secret',              //您的应用的secret
            'secret_backup' => 'test-secret-1',     //您的应用的备用secret，用于更新配置时使用
            'secret_backup_enable' => true,         //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
            'algo' => 'sha256',                     //缺省时，默认为sha1
            'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
            'base_url' => 'http://127.0.0.1:9501',  //api地址host, 例如： http://127.0.0.1:9501
        ],
    ]
];
```

## 路由
- hyperf-project/config/routes.php 添加
```php

Router::get('api/demo', 'App\Controller\Test\FirstController@index', ['middleware' => [\Nick\Signature\Api\Middleware\SignatureVerifyMiddleware::class]]);
```
- 或
```php
Router::addGroup(
    '/api',
    function () {
        Router::get('/demo', 'App\Controller\Customer\LoginCustomerLevelController@index');

    },
    ['middleware' => [\Nick\Signature\Api\Middleware\SignatureVerifyMiddleware::class]]
);
```

## 使用

```php
use Nick\Signature\Api\Http\RequestProxy;

$proxy = (new RequestProxy);
// 或
$proxy = RequestProxy::getInstance();

$proxy->setConfig([
    'enable' => true,                       //是否可用
    'name' => '测试',                        //您的应用名称
    'key' => 'test-key',                    //您的应用的key
    'secret' => 'test-secret',              //您的应用的secret
    'secret_backup' => 'test-secret-1',     //您的应用的备用secret，用于更新配置时使用
    'secret_backup_enable' => false,        //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
    'algo' => 'sha256',                     //缺省时，默认为sha1
    'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
    'base_url' => 'http://127.0.0.1:9501',  //api地址host, 例如： http://127.0.0.1:9501
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
```