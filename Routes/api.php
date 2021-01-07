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
//导出
Route::get('dormitory/buildings/export', 'DormController@export'); //宿舍楼导出
Route::get('dormitory/room/export',       'DormRoomController@export'); //宿舍导出
Route::get('dormitory/beds/export',       'DormBedsController@export'); //床位导出
Route::get('dormitory/history/access/record/export',       'DormHistoryController@access_export'); //导出学生通行记录
Route::get('dormitory/history/export',       'DormHistoryController@export'); //住宿历史导出

//,'middleware'=>['refresh:dorm','DormPermission']
Route::group(['prefix'=>'dormitory','middleware'=>['refresh:dorm','DormPermission']],function ($api){ //'domain' => 'dorm.hnrtxx.com','middleware'=>'refresh'
    //楼宇
    $api->group(['prefix'=>'buildings'],function ($api){
        $api->post('list',         'DormController@lists');//宿舍楼宇列表
        $api->post('add',          'DormController@add');//添加宿舍楼宇
        $api->post('edit',         'DormController@edit');//编辑宿舍楼宇
        $api->post('del',          'DormController@del');//删除宿舍楼宇
        $api->post('cate/add',     'DormController@add_cate');//添加楼宇、宿舍类型
        $api->post('cate/edit',    'DormController@edit_cate');//编辑楼宇、宿舍类型
        $api->post('cate/del',     'DormController@del_cate');//删除楼宇、宿舍类型
        $api->post('cate/list',    'DormController@cate_list');//楼宇、宿舍类型列表
        $api->post('binddevice',   'DormController@bindDevice');//楼宇分配设备


    });
    //宿舍
    $api->group(['prefix'=>'room'],function ($api){
        $api->post('list',         'DormRoomController@lists');//宿舍列表
        $api->post('add',          'DormRoomController@add');//添加宿舍
        $api->post('edit',         'DormRoomController@edit');//编辑宿舍
        $api->post('del',          'DormRoomController@del');//删除宿舍

    });
    //床位
    $api->group(['prefix'=>'beds'],function ($api){
        $api->post('list',         'DormBedsController@lists');//床位列表
        $api->post('detail',       'DormBedsController@detail');//床位详情
        $api->post('change',       'DormBedsController@change');//调宿
        $api->post('add',          'DormBedsController@add');//分配宿舍
        $api->post('del',          'DormBedsController@del');//删除床位人员
        $api->post('batch/users', 'DormBedsController@users');//批量退宿人员列表

    });

    //住宿历史
    $api->group(['prefix'=>'history'],function ($api){
        $api->post('list',         'DormHistoryController@lists');//住宿历史列表
        $api->post('access/record',       'DormHistoryController@student_access'); //学生、教师通行记录
        //晚归记录
        $api->post('later',         'DormHistoryController@later');//晚归记录
        $api->post('noBack',         'DormHistoryController@noBack');//未归记录

    });

    //楼宇实时查询
    $api->group(['prefix'=>'information'],function ($api){
        $api->post('realtime',         'InformationController@realtime');//实时查寝
        $api->post('data',              'InformationController@data');//综合数据

    });

    //管理员相关
    $api->group(['prefix' => 'admin'], function ($apione) {
        $apione->post('logout',        'AuthController@logout');//退出
        $apione->post('add',           'AdminController@create');//添加管理员
        $apione->post('lists',         'AdminController@lists');//获取管理员列表
        $apione->post('delete',        'AdminController@delete');//禁用管理员
        $apione->post('binddorm',      'AdminController@bindDorm');//绑定宿舍
        $apione->post('changePwd',     'AdminController@changePwd');//修改密码
        $apione->post('setsysconfig',  'AdminController@setSysconfig');//系统设置
        $apione->post('getsysconfig',  'AdminController@getSysconfig');//获取系统配置

    });

    //设备相关
    $api->group(['prefix' => 'device'], function ($apione) {
        $apione->post('lists',             'DeviceController@lists');//获取设备列表
        $apione->post('info',              'DeviceController@info');//获取设备的详情
        $apione->post('delete',            'DeviceController@delete');//删除设备
        $apione->post('edit',              'DeviceController@edit');//编辑设备
        $apione->post('getpersonbydevice', 'DeviceController@getPersonByDevice');//编辑设备
    });
});


