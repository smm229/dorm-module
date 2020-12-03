<?php

namespace Modules\Dorm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthDel
{
    /**
     * 角色删除权限
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->module_role == 'superadmin'){ //平台内部访问
            return $next($request);
        }
        if (auth()->user()->id !=1) {//不是超管
            throw new AccessDeniedHttpException('无权操作');
        }
        return $next($request);
    }
}
