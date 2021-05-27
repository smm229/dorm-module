<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//宿舍相关api


Route::post('/dormitory/auth/login', 'AuthController@login');//登录

Route::group(['prefix'=>'dormitory','middleware'=>['refresh:dorm','AdminLog']],function ($api){ //'domain' => 'dorm.hnrtxx.com','middleware'=>'refresh'
    //导出
    $api->get('buildings/export', 'DormController@export'); //宿舍楼导出
    $api->get('room/export',       'DormRoomController@export'); //宿舍导出
    $api->get('beds/export',       'DormBedsController@export'); //床位导出
    $api->get('history/access/record/export',       'DormHistoryController@access_export'); //导出学生通行记录
    $api->get('history/export',       'DormHistoryController@export'); //住宿历史导出
    $api->get('history/access/later/export',       'DormHistoryController@later_export'); //导出晚归记录
    $api->get('history/access/no_back/export',       'DormHistoryController@no_back_export'); //导出未归记录
    $api->get('history/access/no_record/export',       'DormHistoryController@no_record_export'); //导出多天无记录

    $api->group(['middleware'=>['DormPermission']],function ($api) {  //权限管理
        //楼宇
        $api->group(['prefix' => 'buildings'], function ($api) {
            $api->post('list', 'DormController@lists');//宿舍楼宇or权限组列表
            $api->post('add', 'DormController@add');//添加宿舍楼宇or权限组
            $api->post('edit', 'DormController@edit');//编辑宿舍楼宇or权限组
            $api->post('del', 'DormController@del');//删除宿舍楼宇or权限组
            $api->post('cate/add', 'DormController@add_cate');//添加楼宇、宿舍类型
            $api->post('cate/edit', 'DormController@edit_cate');//编辑楼宇、宿舍类型
            $api->post('cate/del', 'DormController@del_cate');//删除楼宇、宿舍类型
            $api->post('cate/list', 'DormController@cate_list');//楼宇、宿舍类型列表
            $api->post('binddevice', 'DormController@bindDevice');//楼宇分配设备


        });
        //宿舍
        $api->group(['prefix' => 'room'], function ($api) {
            $api->post('list', 'DormRoomController@lists');//宿舍列表
            $api->post('add', 'DormRoomController@add');//添加宿舍
            $api->post('addList', 'DormRoomController@addList');//批量添加宿舍
            $api->post('edit', 'DormRoomController@edit');//编辑宿舍
            $api->post('del', 'DormRoomController@del');//删除宿舍

        });
        //床位
        $api->group(['prefix' => 'beds'], function ($api) {
            $api->post('list', 'DormBedsController@lists');//床位列表
            $api->post('detail', 'DormBedsController@detail');//床位详情
            $api->post('change', 'DormBedsController@change');//调宿
            $api->post('add', 'DormBedsController@add');//分配宿舍
            $api->post('del', 'DormBedsController@del');//删除床位人员
            $api->post('batch/users', 'DormBedsController@users');//批量退宿人员列表
            $api->post('import', 'DormBedsController@import');//住宿分配导入
        });

        //住宿历史
        $api->group(['prefix' => 'history'], function ($api) {
            $api->post('list', 'DormHistoryController@lists');//住宿历史列表
            $api->post('access/record', 'DormHistoryController@student_access'); //学生、教师通行记录
            //晚归记录
            $api->post('later', 'DormHistoryController@later');//晚归记录
            $api->post('noBack', 'DormHistoryController@noBack');//未归记录
            $api->post('noRecord', 'DormHistoryController@noRecord');//多日无记录
            $api->post('strange/record',    'DormHistoryController@strange');//陌生人识别记录

        });

        //楼宇实时查询
        $api->group(['prefix' => 'information'], function ($api) {
            $api->post('realtime', 'InformationController@realtime');//实时查寝
            $api->post('data', 'InformationController@data');//综合数据
            $api->post('index', 'InformationController@index');//宿管首页
        });

        //管理员相关
        $api->group(['prefix' => 'admin'], function ($apione) {
            $apione->post('logout', 'AuthController@logout');//退出
            $apione->post('add', 'AdminController@create');//添加管理员
            $apione->post('edit', 'AdminController@edit');//修改管理员
            $apione->post('del', 'AdminController@del');//删除管理员
            $apione->post('getLog', 'AdminController@getAadminlog');//管理员操作日志
            $apione->post('lists', 'AdminController@lists');//获取管理员列表
            $apione->post('editstatus', 'AdminController@editstatus');//禁用or开放管理员
            $apione->post('binddorm', 'AdminController@bindDorm');//绑定宿舍
            $apione->post('changePwd', 'AdminController@changePwd');//修改密码
            $apione->post('setsysconfig', 'AdminController@setSysconfig');//系统设置
            $apione->post('getsysconfig', 'AdminController@getSysconfig');//获取系统配置

        });

        //设备相关
        $api->group(['prefix' => 'device'], function ($apione) {
            $apione->post('lists', 'DeviceController@lists');//获取设备列表
            $apione->post('alarm/lists', 'DeviceController@alarm');//获取设备告警列表
            $apione->post('alarm/relieve', 'DeviceController@relieve');//解除设备告警
            $apione->post('info', 'DeviceController@info');//获取设备的详情
            $apione->post('delete', 'DeviceController@delete');//删除设备
            $apione->post('edit', 'DeviceController@edit');//编辑设备
            $apione->post('getpersonbydevice', 'DeviceController@getPersonByDevice');//编辑设备
            $apione->post('electric', 'DeviceController@electric');//电控列表
        });

        //访客相关
        $api->group(['prefix' => 'visit'], function ($apione) {
            $apione->post('add',  'VisitController@create');//添加访客
            $apione->post('edit', 'VisitController@edit');//编辑访客
            $apione->post('del',  'VisitController@del');//删除访客
            $apione->post('list',  'VisitController@lists');//访客列表
            $apione->post('logss', 'VisitController@logss');//访客通行记录
            $apione->post('state', 'VisitController@state');//批量审核
        });

        //权限组相关
        $api->group(['prefix' => 'group'], function ($apione) {
            $apione->post('addperson',      'DormGroupController@addperson');//权限分配人员
            $apione->post('delperson',      'DormGroupController@delperson');//权限分配人员
            $apione->post('getpersonlist',  'DormGroupController@getpersonlist');//权限组下人员列表
            $apione->post('getunpersonlist','DormGroupController@getunpersonlist');//权限组下未人员列表
            $apione->post('getdevicelist',  'DormGroupController@getdevicelist');//权限组下未分组设备
        });

        //黑名单
        $api->group(['prefix' => 'black'], function ($apione) {
            $apione->post('add',  'DormBlackController@add');    //添加黑名单
        });
        //菜单规则
        $api->group(['prefix'=>'auth'],function ($api){
            $api->post('authrule/list', 'AuthRuleController@lists'); //菜单规则列表
            $api->post('authrule/add', 'AuthRuleController@add'); //菜单规则添加
            $api->post('authrule/edit', 'AuthRuleController@edit'); //菜单规则修改
            $api->post('authrule/del', 'AuthRuleController@del'); //菜单规则删除
        });

        //角色组
        $api->group(['prefix'=>'auth'],function ($api){
            $api->post('authgroup/list', 'AuthGroupController@lists'); //角色组列表
            $api->post('authgroup/add', 'AuthGroupController@add'); //角色组添加
            $api->post('authgroup/edit', 'AuthGroupController@edit'); //角色组修改
            $api->post('authgroup/del', 'AuthGroupController@del'); //角色组删除
            $api->post('authgroup/info', 'AuthGroupController@info'); //修改详情
            $api->post('authgroup/menulist', 'AuthGroupController@menulist'); //修改详情
        });

        //登录日志
        $api->group(['prefix'=>'log'],function ($api){
            $api->post('list', 'LoginLogController@lists'); //菜单规则列表
        });


    });

    //星云设备
    $api->group(['prefix'=>'nebula'], function ($api) {
        $api->post('PersonPackageList', 'NebulaController@PersonPackageList');//人员库列表
    });

});

