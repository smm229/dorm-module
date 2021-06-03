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
                $details = self::getDetails(substr($request->path(),4));
                if ($contents) {
                    $insert = [
                        'username' => $data['username'],
                        'idnum'    => $data['idnum'],
                        'ip'       => $request->getClientIp(),
                        'contents' => $contents,
                        'details'  => $details,
                        'user_id'  => auth()->user()->id
                    ];
                    DB::table('dormitory_adminlog')->insert($insert);
                }
            }catch (\Exception $e) {
                return showMsg('登录信息错误, 请联系管理员', 401);
            }

        }

        return $next($request);
    }


    static private function getDetails($api){
        $apis = [

            'dormitory/history/access/record/export'              => '导出学生通行记录',
            'dormitory/history/export'                            => '住宿历史导出',
            'dormitory/history/access/later/export'               => '导出晚归记录',
            'dormitory/history/access/no_back/export'             => '导出未归记录',
            'dormitory/history/access/no_record/export'           => '导出多天无记录',

            'dormitory/buildings/export'                          => '宿舍楼导出',
            'dormitory/buildings/list'                            => '宿舍楼宇or权限组列表',
            'dormitory/buildings/add'                             => '添加宿舍楼宇or权限组',
            'dormitory/buildings/edit'                            => '编辑宿舍楼宇or权限组',
            'dormitory/buildings/del'                             => '删除宿舍楼宇or权限组',
            'dormitory/buildings/cate/add'                        => '添加楼宇、宿舍类型',
            'dormitory/buildings/cate/edit'                       => '编辑楼宇、宿舍类型',
            'dormitory/buildings/cate/del'                        => '删除楼宇、宿舍类型',
            'dormitory/buildings/cate/list'                       => '楼宇、宿舍类型列表',

            'dormitory/room/export'                               => '宿舍导出',
            'dormitory/room/list'                                 => '宿舍列表',
            'dormitory/room/add'                                  => '添加宿舍',
            'dormitory/room/addList'                              => '批量添加宿舍',
            'dormitory/room/edit'                                 => '编辑宿舍',
            'dormitory/room/del'                                  => '删除宿舍',

            'dormitory/beds/export'                               => '床位导出',
            'dormitory/beds/list'                                 => '床位列表',
            'dormitory/beds/detail'                               => '床位详情',
            'dormitory/beds/change'                               => '调宿',
            'dormitory/beds/add'                                  => '分配宿舍',
            'dormitory/beds/del'                                  => '删除床位人员',
            'dormitory/batch/users'                               => '批量退宿人员列表',
            'dormitory/beds/import'                               => '住宿分配导入',

            'dormitory/history/list'                              => '住宿历史列表',
            'dormitory/history/access/record'                     => '学生、教师通行记录',
            'dormitory/history/later'                             => '晚归记录',
            'dormitory/history/noBack'                            => '未归记录',
            'dormitory/history/noRecord'                          => '多日无记录',
            'dormitory/history/strange/record'                    => '陌生人识别记录',

            'dormitory/information/realtime'                      => '实时查寝',
            'dormitory/information/data'                          => '综合数据',
            'dormitory/information/index'                         => '宿管首页',

            'dormitory/admin/logout'                              => '退出',
            'dormitory/admin/add'                                 => '添加管理员',
            'dormitory/admin/edit'                                => '修改管理员',
            'dormitory/admin/del'                                 => '删除管理员',
            'dormitory/admin/getLog'                              => '管理员操作日志',
            'dormitory/admin/getLogid'                            => '指定管理员操作日志',
            'dormitory/admin/lists'                               => '获取管理员列表',
            'dormitory/admin/editstatus'                          => '禁用or开放管理员',
            'dormitory/admin/binddorm'                            => '绑定宿舍',
            'dormitory/admin/changePwd'                           => '修改密码',
            'dormitory/admin/setsysconfig'                        => '系统设置',
            'dormitory/admin/getsysconfig'                        => '获取系统配置',

            'dormitory/device/lists'                              => '获取设备列表',
            'dormitory/device/alarm/lists'                        => '获取设备告警列表',
            'dormitory/device/alarm/relieve'                      => '解除设备告警',
            'dormitory/device/info'                               => '获取设备的详情',
            'dormitory/device/delete'                             => '删除设备',
            'dormitory/device/edit'                               => '编辑设备',
            'dormitory/device/getpersonbydevice'                  => '编辑设备',
            'dormitory/device/electric'                           => '电控列表',

            'dormitory/visit/add'                                 => '添加访客',
            'dormitory/visit/edit'                                => '编辑访客',
            'dormitory/visit/del'                                 => '删除访客',
            'dormitory/visit/list'                                => '访客列表',
            'dormitory/visit/logss'                               => '访客通行记录',
            'dormitory/visit/state'                               => '批量审核',

            'dormitory/group/addperson'                           => '权限分配人员',
            'dormitory/group/delperson'                           => '权限分配人员',
            'dormitory/group/getpersonlist'                       => '权限组下人员列表',
            'dormitory/group/getunpersonlist'                     => '权限组下未人员列表',
            'dormitory/group/getdevicelist'                       => '权限组下未分组设备',

            'dormitory/black/add'                                 => '添加黑名单',

            'dormitory/auth/authrule/list'                        => '菜单规则列表',
            'dormitory/auth/authrule/add'                         => '菜单规则添加',
            'dormitory/auth/authrule/edit'                        => '菜单规则修改',
            'dormitory/auth/authrule/del'                         => '菜单规则删除',

            'dormitory/auth/authgroup/list'                       => '角色组列表',
            'dormitory/auth/authgroup/add'                        => '角色组添加',
            'dormitory/auth/authgroup/edit'                       => '角色组修改',
            'dormitory/auth/authgroup/del'                        => '角色组删除',
            'dormitory/auth/authgroup/info'                       => '角色组详情',
            'dormitory/auth/authgroup/menulist'                   => '当前用户可查看菜单',

            'dormitory/log/list'                                  => '登录日志列表',
        ];
        return isset($apis[$api])?$apis[$api]:[];
    }

    static private function getContents($api) {
        $apis = [

            'dormitory/history/access/record/export'              => '学生通勤',
            'dormitory/history/export'                            => '住宿历史',
            'dormitory/history/access/later/export'               => '晚归记录',
            'dormitory/history/access/no_back/export'             => '未归记录',
            'dormitory/history/access/no_record/export'           => '多天无记录人员',

            'dormitory/buildings/export'                          => '宿舍楼信息',
            'dormitory/buildings/list'                            => '宿舍楼信息',
            'dormitory/buildings/add'                             => '宿舍楼信息',
            'dormitory/buildings/edit'                            => '宿舍楼信息',
            'dormitory/buildings/del'                             => '宿舍楼信息',
            'dormitory/buildings/cate/add'                        => '宿舍楼信息',
            'dormitory/buildings/cate/edit'                       => '宿舍楼信息',
            'dormitory/buildings/cate/del'                        => '宿舍楼信息',
            'dormitory/buildings/cate/list'                       => '宿舍楼信息',

            'dormitory/room/export'                               => '宿舍信息',
            'dormitory/room/list'                                 => '宿舍信息',
            'dormitory/room/add'                                  => '宿舍信息',
            'dormitory/room/addList'                              => '宿舍信息',
            'dormitory/room/edit'                                 => '宿舍信息',
            'dormitory/room/del'                                  => '宿舍信息',

            'dormitory/beds/export'                               => '床位信息',
            'dormitory/beds/list'                                 => '床位信息',
            'dormitory/beds/detail'                               => '床位信息',
            'dormitory/beds/change'                               => '床位信息',
            'dormitory/beds/add'                                  => '床位信息',
            'dormitory/beds/del'                                  => '床位信息',
            'dormitory/batch/users'                               => '批量退宿',
            'dormitory/beds/import'                               => '床位信息',

            'dormitory/history/list'                              => '住宿历史',
            'dormitory/history/access/record'                     => '学生、教师通行记录',
            'dormitory/history/later'                             => '晚归记录',
            'dormitory/history/noBack'                            => '未归记录',
            'dormitory/history/noRecord'                          => '多日无记录',
            'dormitory/history/strange/record'                    => '陌生人识别记录',

            'dormitory/information/realtime'                      => '楼宇实时查寝',
            'dormitory/information/data'                          => '综合数据',
            'dormitory/information/index'                         => '宿管首页',

            'dormitory/admin/logout'                              => '管理员退出',
            'dormitory/admin/add'                                 => '账号权限管理',
            'dormitory/admin/edit'                                => '账号权限管理',
            'dormitory/admin/del'                                 => '账号权限管理',
            'dormitory/admin/getLog'                              => '账号权限管理',
            'dormitory/admin/getLogid'                              => '账号权限管理',
            'dormitory/admin/lists'                               => '账号权限管理',
            'dormitory/admin/editstatus'                          => '账号权限管理',
            'dormitory/admin/binddorm'                            => '绑定宿舍',
            'dormitory/admin/changePwd'                           => '修改密码',
            'dormitory/admin/setsysconfig'                        => '系统参数设置',
            'dormitory/admin/getsysconfig'                        => '系统参数设置',

            'dormitory/device/lists'                              => '设备列表',
            'dormitory/device/alarm/lists'                        => '设备警告',
            'dormitory/device/alarm/relieve'                      => '设备列表',
            'dormitory/device/info'                               => '设备列表',
            'dormitory/device/delete'                             => '设备列表',
            'dormitory/device/edit'                               => '设备列表',
            'dormitory/device/getpersonbydevice'                  => '设备列表',
            'dormitory/device/electric'                           => '设备列表',

            'dormitory/visit/add'                                 => '访客管理',
            'dormitory/visit/edit'                                => '访客管理',
            'dormitory/visit/del'                                 => '访客管理',
            'dormitory/visit/list'                                => '访客管理',
            'dormitory/visit/logss'                               => '访客管理',
            'dormitory/visit/state'                               => '访客管理',

            'dormitory/group/addperson'                           => '权限分配人员',
            'dormitory/group/delperson'                           => '权限分配人员',
            'dormitory/group/getpersonlist'                       => '权限组下人员列表',
            'dormitory/group/getunpersonlist'                     => '权限组下未人员列表',
            'dormitory/group/getdevicelist'                       => '权限组下未分组设备',

            'dormitory/black/add'                                 => '黑名单管理',

            'dormitory/auth/authrule/list'                        => '菜单规则',
            'dormitory/auth/authrule/add'                         => '菜单规则',
            'dormitory/auth/authrule/edit'                        => '菜单规则',
            'dormitory/auth/authrule/del'                         => '菜单规则',

            'dormitory/auth/authgroup/list'                       => '角色组管理',
            'dormitory/auth/authgroup/add'                        => '角色组管理',
            'dormitory/auth/authgroup/edit'                       => '角色组管理',
            'dormitory/auth/authgroup/del'                        => '角色组管理',
            'dormitory/auth/authgroup/info'                       => '角色组管理',
            'dormitory/auth/authgroup/menulist'                   => '账号权限管理',

            'dormitory/log/list'                                  => '登录日志',
        ];
        return isset($apis[$api])?$apis[$api]:[];
    }
}
