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
Route::group(['prefix'=>'dorm','middleware'=>'refresh'],function (){ //'domain' => 'dorm.hnrtxx.com',
    Route::any('/aa', 'DormController@index');
});
