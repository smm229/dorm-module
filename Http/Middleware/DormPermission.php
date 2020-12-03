<?php

namespace Modules\Dorm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DormPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->module_role,['superadmin','dorm'])) {//不是宿舍模块、超管
            throw new AccessDeniedHttpException('无权操作');
        }
        return $next($request);
    }
}
