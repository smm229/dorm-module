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

Route::group(['prefix'=>'dormitory','middleware'=>'DormPermission'],function ($api){ //'domain' => 'dorm.hnrtxx.com','middleware'=>'refresh'
    $api->group(['prefix'=>'buildings'],function ($api){
        $api->post('/list',         'DormController@lists');//宿舍楼宇列表
        $api->post('/add',          'DormController@add');//添加宿舍楼宇
        $api->post('/edit',         'DormController@edit');//编辑宿舍楼宇
        $api->post('/del',          'DormController@del');//删除宿舍楼宇
    });

    Route::post('/bb', 'DormController@create');



    Route::post('categorylist', 'AdminController@categoryList');
    Route::post('teacherlist', 'AdminController@teacherList');
    Route::post('teacheradd', 'AdminController@create');

});
