<?php

namespace app\middleware\api;

use app\service\api\AuthServiceFacade;
use app\service\api\CheckSignServiceFacade;
use app\service\api\UserServiceFacade;
use laytp\BaseMiddleware;
use think\Request;

class Auth extends BaseMiddleware
{
    /**
     * 执行中间件
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (AuthServiceFacade::needLogin()) {
            if (AuthServiceFacade::checkVerifyToken()) {
                return $next($request);
            }
            return $this->error(AuthServiceFacade::getError(),10401);
        }
        return $next($request);
    }
}