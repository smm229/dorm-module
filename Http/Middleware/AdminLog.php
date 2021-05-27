<?php

namespace Modules\Dorm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminLog{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next){

        if(empty(auth()->user())){
            throw new UnauthorizedException('请先登录');
        }

        if(auth()->user() && $request->module_role == "dorm"){
            try {
                $data['username'] = auth()->user()->username;
                $data['idnum'] = auth()->user()->idnum;
                if (!isset($data['username']) || !isset($data['idnum'])) {
                    return showMsg('获取信息失败, 请联系管理员', 401);
                }
                $contents = self::getContents(substr($request->path(),4));
                if ($contents) {
                    $insert = [
                        'username' => $data['username'],
                        'idnum'    => $data['idnum'],
                        'ip'       => $request->getClientIp(),
                        'contents' => $contents
                    ];
                    DB::table('dormitory_adminlog')->insert($insert);
                }
            }catch (\Exception $e) {
                return showMsg('登录信息错误, 请联系管理员', 401);
            }

        }

        return $next($request);
    }

    static private function getContents($api) {
        $apis = [
            'dormitory/auth/login'   => '登录系统',
            'dormitory/information/index'   => '首页',
            'dormitory/buildings/export'    => '宿舍楼导出',
            'dormitory/room/export'       => '宿舍导出',
            'dormitory/beds/export'  => '床位导出',
            'dormitory/history/access/record/export' => '导出学生通行记录',
            'dormitory/history/export'  => '住宿历史导出',
            'dormitory/history/access/later/export' => '导出晚归记录',
            'dormitory/history/access/no_back/export'     => '导出未归记录',
            'dormitory/history/access/no_record/export'     => '导出多天无记录',

            'dormitory/admin/lists'     => '账号权限管理-首页',
            'dormitory/admin/add'     => '账号权限管理-添加',
            'dormitory/auth/authgroup/list'     => '角色组-首页',
            'dormitory/auth/authgroup/add'     => '角色组-添加',
        ];
        return isset($apis[$api])?$apis[$api]:[];
    }
}
