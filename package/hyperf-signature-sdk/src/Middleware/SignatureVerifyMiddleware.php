<?php

declare(strict_types=1);


namespace Nick\Signature\Api\Middleware;

use Nick\Signature\Api\Util\Hmac;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SignatureVerifyMiddleware implements MiddlewareInterface
{
    private $headers;
   
    private $signatureParams;

    private $message;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (env('APP_ENV') == 'dev') {
            $authOk = true;
        } else {
            try {
                // 签名认证
                $authOk = $this->authorize();
            } catch (\Exception $e) {
                $authOk = false;
                $this->message = $e->getMessage();
            }
        }

        if ($authOk) {
            return $handler->handle($request);
        } else {
            return $this->response->json(
                [
                    'code' => 401,
                    'message' => $this->message ?? '请你完成密钥签名认证，请在请求头部(Header)设置客户公钥(x-app-token)和hmacsha1签名(x-app-signature)',
                    'data' => [
                        'error' => '中间件签名验证无效，阻止继续向下执行',
                        'headers' => $this->headers ?? $this->request->all(),
                        'signature_params' => $this->signatureParams
                    ],
                ]
            );
        }
    }

    /**
     * Hmac 签名验证
     */
    protected function authorize(): bool
    {
        // 获取校验码
        $key = 'none';
        $algo = 'sha1';
        $signature = 'none';
        if ($this->request->hasHeader('x-app-key') || $this->request->hasHeader('x-app-signature')) {
            $key = $this->request->getHeaderLine('x-app-key', 'none');
            $algo = $this->request->getHeaderLine('x-app-algo', 'none');
            $signature = $this->request->getHeaderLine('x-app-signature', 'none');
        }

        $secret = '';
        $config = config('app-sign');
        if (!isset($config['app']) || !isset($config['app'][$key])) {
            $this->message = "app: key={$key} not found.";
            return false;
        } else {
            $appConfig = $config['app'][$key];
            if (!$this->request->hasHeader('x-app-backup-secret-enable') || !boolval($this->request->getHeaderLine('x-app-backup-secret-enable'))) {
                // 如果客户端使用的是确认的密钥， 那么服务端也用确认的密钥进行验证
                if (!isset($appConfig['secret']) || !$appConfig['secret']) {
                    //判断服务端的备份密钥是否为空
                    $this->message = "app: key={$key} 的 secret 在服务端配置为空，请检查配置.";
                    return false;
                }
                $secret = $appConfig['secret'];
            } else {
                // 如果客户端使用的是备用的密钥， 那么服务端也用备用的密钥进行验证
                if (!isset($appConfig['secret_backup']) || !$appConfig['secret_backup']) {
                    //判断服务端的备份密钥是否为空
                    $this->message = "app: key={$key} 的 secret_backup 在服务端配置为空，请检查配置.";
                    return false;
                }
                $secret = $appConfig['secret_backup'];
            }
        }

        // 接口验证
        $this->signatureParams = $this->request->all();

        $this->signatureParams['api_method'] = $this->getRequestMethod();
        if ($this->request->hasHeader(('x-app-experied-at'))) {
            $this->signatureParams['api_experied_at'] = $this->request->getHeaderLine('x-app-experied-at');
        } 
   
        ksort($this->signatureParams);

        $tokenBeforeSignature = strtolower(http_build_query($this->signatureParams));
        $signatureConfirmation = Hmac::signature($algo, $secret, $tokenBeforeSignature);

        $this->headers = [
            'x-app-key' => $key,
            'x-app-algo' => $algo,
            'x-app-signature' => $signature,
            'signatureConfirmation' => $signatureConfirmation,
            'tokenBeforeSignature' => $tokenBeforeSignature
        ];

        // 判断合法，即可进行后面的数据处理
        if ($signatureConfirmation != $signature) {
            $this->message = "app: key={$key} 的 签名验证失败，请检查密钥或逻辑.";
            return false;
        }

        return true;
    }

    /**
     * 获取请求方法名(小写)
     *
     * @return string
     */
    private function getRequestMethod()
    {
        if ($this->request->isMethod('GET')) {
            return 'get';
        } else  if ($this->request->isMethod('POST')) {
            return 'post';
        }  if ($this->request->isMethod('PUT')) {
            return 'put';
        } if ($this->request->isMethod('DELETE')) {
            return 'delete';
        } if ($this->request->isMethod('OPTION')) {
            return 'option';
        }
    }
}
