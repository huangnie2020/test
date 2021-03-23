<?php

namespace Nick\Signature\Api\Facade;

use Illuminate\Support\Facades\Facade;
use Nick\Signature\Api\Http\RequestProxy;

/**
 * 门面类可以静态调用
 */
class RequestFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RequestProxy::class;
    }
}
