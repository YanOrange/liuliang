<?php

namespace app\middleware\api;

use app\service\api\CheckSignServiceFacade;
use app\service\ConfServiceFacade;
use laytp\BaseMiddleware;
use laytp\traits\JsonReturn;
use think\Request;

/**
 * 签名验证中间件
 */
class CheckSign extends BaseMiddleware
{
    use JsonReturn;

    /**
     * 执行中间件
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (CheckSignServiceFacade::needCheckSign()) {
            if (CheckSignServiceFacade::checkVerifySign()) {
                return $next($request);
            }
            return $this->error(CheckSignServiceFacade::getError(),10402);
        }
        return $next($request);
    }
}