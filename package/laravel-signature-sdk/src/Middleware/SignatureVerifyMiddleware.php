<?php

namespace Nick\Signature\Api\Middleware;

use Closure;
use Exception;
use Nick\Signature\Api\Util\Hmac;

/**
 * 签名验证中间件
 */
class SignatureVerifyMiddleware
{
    private $headers;

    private $signatureParams;

    private $message;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $keyItems
     * @return mixed
     */
    public function handle($request, Closure $next, $keyItems = null)
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'dev') {
            $authOk = true;
        } else {
            try {
                // 签名认证
                $authOk = $this->authorize($request, $keyItems);
            } catch (Exception $e) {
                $authOk = false;
                $this->message = $e->getMessage();
            }
        }

        // $request->headers->set('x-csrf-token', csrf_field());
        if ($authOk) {
            return $next($request);
        } else {
            return response()->json([
                'code' => 401,
                'message' => $this->message ?? '请你完成密钥签名认证，请在请求头部(Header)设置客户公钥(x-app-key)和hmacsha1签名(x-app-signature)等参数',
                'data' => [
                    'error' => '中间件签名验证无效，阻止继续向下执行',
                    'signature_params' => $this->signatureParams,
                    'headrs' => $this->headers
                ],
            ]);
        }
    }

    /**
     * Hmac 签名验证
     *
     * @param \Illuminate\Http\Request $request
     * @param  string|null  $keyItems
     * @return boolean
     */
    protected function authorize($request, $keyItems = null): bool
    {
        // 获取校验码
        $key = 'test';
        $algo = 'sha1';
        $signature = 'none';
        if ($request->hasHeader('x-app-key') || $request->hasHeader('x-app-signature')) {
            $key = $request->header('x-app-key', 'test');
            $algo = $request->header('x-app-algo', 'sha1');
            $signature = $request->header('x-app-signature', 'none');
        }

        if ($keyItems) {
            $keyArr = array_filter(explode(':', $keyItems));
            if (!in_array($key, $keyArr)) {
                $this->message = "app: key={$key} forbid visit.";
                return false;
            }
        }

        $secret = '';
        $config = config('app-sign');
        if (!isset($config['app']) || !isset($config['app'][$key])) {
            $this->message = "app: key={$key} not found.";
            return false;
        } else {
            $appConfig = $config['app'][$key];
            if (!$request->hasHeader('x-app-backup-secret-enable') || !boolval($request->header('x-app-backup-secret-enable'))) {
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
        $this->signatureParams = $request->all();

        $this->signatureParams['api_method'] = strtolower($request->method());
        if ($request->hasHeader(('x-app-experied-at'))) {
            $this->signatureParams['api_experied_at'] = $request->header('x-app-experied-at');
        } 
   
        ksort($this->signatureParams);

        $tokenBeforeSignature = strtolower(http_build_query($this->signatureParams));
        $signatureConfirmation = Hmac::signature($algo, $secret, $tokenBeforeSignature);

        $this->headers = [
            'x-app-key' => $key,
            'x-app-algo' => $algo,
            'x-app-signature' => $signature,
            'signatureConfirmation' => $signatureConfirmation,
            'tokenBeforeSignature' => $tokenBeforeSignature,
        ];

        // 判断合法，即可进行后面的数据处理
        if ($signatureConfirmation != $signature) {
            $this->message = "app: key={$key} 的 签名验证失败，请检查密钥或逻辑.";
            return false;
        }

        return true;
    }
}