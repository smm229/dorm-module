<?php

namespace Modules\Dorm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
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
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        //只查询自己权限的楼宇
        if(!RedisGet('builds-'.$uid)) { //不存在缓存
            $idnum = auth()->user() ? auth()->user()->idnum : '';
            if ($uid == 1) { //超管
                $builds = DormitoryGroup::whereType(1)->pluck('id')->toArray();
            } else {
                $builds = DormitoryUsersBuilding::where('idnum', $idnum)->pluck('buildid')->toArray();
            }
            RedisSet('builds-'.$uid,$builds,7200);
        }
        return $next($request);
    }
}
