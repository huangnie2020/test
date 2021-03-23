# Laravel Signature Sdk

兼容>=PHP7.1版本

## 安装

1、在composer.json文件的`repositories`中添加仓库地址

```php
// 添加 path 类型源
"repositories": [
        {
            "type": "path",
            "url": "../laraval-signature"
        }
]
```

2、配置
- laravel-project/app/Http/Kernel.php 添加中间件
```php
protected $routeMiddleware = [
    'api.signature' => \Nick\Signature\Api\Middleware\SignatureVerifyMiddleware::class,
    ...
    ...
    ...
];

```
- laravel-project/config/app-sign.php 配置应用参数
```php
return [
    'default' => env('APP_KEY_DEFAULT', 'test-key'),
    'app' => [
        'test-key' => [
            'enable' => true,                       //是否可用
            'name' => '测试',                        //您的应用名称
            'key' => 'test-key',                    //您的应用的key
            'secret' => 'test-secret',              //您的应用的secret
            'secret_backup' => 'test-secret-2',     //您的应用的备用secret，用于更新配置时使用
            'secret_backup_enable' => false,        //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
            'algo' => 'sha256',                     //缺省时，默认为sha1
            'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
        ],
      't2' => [...],
      't3' => [...]
    ]
];
```
- laravel-project/routes/web.php 添加路由
```php
Route::group(['middleware' => ['api.signature:test-key:test-key-2']], function () {

    Route::get('api/demo', 'Test\FirstController@index');
});
```
- :test-key:test-key-2 是传递给中间件的参数，是允许访问的各应用key值(冒号区分key值)，若不传递则有密钥的应用都可以访问

4、更新

```bash
composer update wxa/laraval-signature-sdk
```


## 使用

```php
// 示例一:使用实现类调用
use Nick\Signature\Api\Http\RequestProxy;

$config = config('app-sign')['app']['test'];
// 或
$config = array(
    'enable' => true,                       //是否可用
    'name' => '测试',                        //您的应用名称
    'key' => 'test-key',                    //您的应用的key
    'secret' => 'test-secret',              //您的应用的secret
    'secret_backup' => 'test-secret-2',     //您的应用的备用secret，用于更新配置时使用
    'secret_backup_enable' => false,        //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
    'algo' => 'sha256',                     //缺省时，默认为sha1
    'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
    'base_url' => 'http://127.0.0.1:9501',  //api地址host, 例如： http://127.0.0.1:9501
);

$proxy = (new RequestProxy);
// 或
$proxy = RequestProxy::getInstance();

$proxy->setConfig($config);
$result = $proxy->get('demo', ['user_id'=>1]);

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

```php
// 示例二: 使用门面类静态调用
use Nick\Signature\Api\Facade\RequestFacade;

$config = config('app-sign')['app']['test'];
// 或
$config = array(
    'enable' => true,                       //是否可用
    'name' => '测试',                        //您的应用名称
    'key' => 'test-key',                    //您的应用的key
    'secret' => 'test-secret',              //您的应用的secret
    'secret_backup' => 'test-secret-2',     //您的应用的备用secret，用于更新配置时使用
    'secret_backup_enable' => false,        //是否启用备用secret, 只有启用了配置的备用secret才会有效<安全更新secret>
    'algo' => 'sha256',                     //缺省时，默认为sha1
    'expired' => -1,                        //签名有效时长，单位：秒，负数或零表示无限制
    'base_url' => 'http://127.0.0.1:9501',  //api地址host, 例如： http://127.0.0.1:9501
);

$proxy = RequestFacade::setConfig($config);
$result = $proxy->get('demo', ['refresh' => 0]);

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