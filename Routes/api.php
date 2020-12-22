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
        $api->post('cate/add',    'DormController@add_cate');//添加楼宇、宿舍类型
        $api->post('cate/edit',   'DormController@edit_cate');//编辑楼宇、宿舍类型
        $api->post('cate/del',    'DormController@del_cate');//删除楼宇、宿舍类型
        $api->post('cate/list',   'DormController@cate_list');//楼宇、宿舍类型列表

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

    });

    $api->group(['prefix' => 'admin'], function ($apione) {
        $apione->post('categorylist',  'AdminController@categoryList');
        $apione->post('teacherlist',   'AdminController@teacherList');
        $apione->post('teacheradd',    'AdminController@create');
        $apione->post('adminlist',     'AdminController@adminList');
        $apione->post('delete',        'AdminController@delete');
        $apione->post('binddorm',      'AdminController@bindDorm');
        $apione->post('logout',        'AuthController@logout');//退出

    });

    $api->group(['prefix' => 'facility'], function ($apione) {
        $apione->post('list',          'FacilityController@index');
        $apione->post('edit',          'FacilityController@edit');
        $apione->post('delete',        'FacilityController@delete');
        $apione->post('test',          'FacilityController@test');
    });
});


