<?php

namespace Modules\Dorm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Modules\Dorm\Entities\DormitoryAuthGroup;
use Modules\Dorm\Entities\DormitoryAuthRule;
use Modules\Dorm\Entities\DormitoryAuthUser;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
        if(empty(auth()->user())){
            throw new UnauthorizedException('请先登录');
        }
        $idnum = auth()->user()->idnum;
        //只查询自己权限的楼宇
        if(!RedisGet('builds-'.$idnum)) { //不存在缓存
            if ($idnum == 'admin') { //超管
                $builds = DormitoryGroup::whereType(1)->pluck('id')->toArray();
            } else {
                $builds = DormitoryUsersBuilding::where('idnum', $idnum)->pluck('buildid')->toArray();
            }
            RedisSet('builds-'.$idnum,$builds,7200);

        }

        if (in_array($request->module_role,['superadmin','dorm'])) {

            $route = substr($request->path(),4);//去除api/
            $DormitoryAuthRule =  DormitoryAuthRule::where('url',$route)->first();
            //按钮权限跳出
            if($DormitoryAuthRule){
                if($DormitoryAuthRule['type'] == 2){
                    return $next($request);
                }
            }
            $roleid = DormitoryAuthUser::whereUserid(auth()->user()->id)->pluck('roleid');
            if ($roleid) {
                $user = DormitoryAuthGroup::whereId($roleid)->first();
                $rules = explode(',',$user['rules']);
                $list = DormitoryAuthRule::where(['disable' => 0])->whereIn('id', $rules)->pluck('url')->toArray();;
            }

            if($user['rules'] != "*") { //超管任何权限
                if (!in_array($route, $list)) {
                    return showMsg('无权操作', 403);
                }
            }

        }
        return $next($request);
    }
}
