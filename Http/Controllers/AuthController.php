<?php

namespace Modules\Dorm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Dorm\Entities\DormitoryLoginlog;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Http\Requests\LoginValidate;
use Tymon\JWTAuth\Facades\JWTAuth;
use Zhuzhichao\IpLocationZh\Ip;

class AuthController extends Controller
{
    protected $guard = "dorm";
    /**
     * login
     * @param username idnum 账号
     * @param password password 密码
     */
    public function login(LoginValidate $request)
    {
        //验证码
       /* if (!captcha_api_check($request->captcha, $request->key)){
            return showMsg('验证码不匹配');
        }*/
        $credentials = $request->only('idnum', 'password');
        $user = DormitoryUsers::whereIdnum($credentials['idnum'])->first();
        if(!$user){
            return showMsg('账号信息错误');
        }
        if(!password_verify($credentials['password'] , $user->password )){
            return showMsg('密码输入有误，请重新输入');
        }
        if($user->disable==1){
            return showMsg('禁止登入');
        }

        if($token = $this->guard()->attempt($credentials)){
            $user->remember_token = $token;
            //增加登录日志
            $ip =  $request->getClientIp();
            $city =  Ip::find($ip);
            if($city[2] == ""){
                $city[2] = '本机地址';
            }

            $data = [
                'city' => $city[2],
                'idnum'=> $user->idnum,
                'ip'=>$ip,
                'username'=>$user->username
            ];
            DormitoryLoginlog::insert($data);

            return showMsg('登录成功',200,$user);
        }

        return showMsg('登陆失败');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard($this->guard);
    }

    /*
     * 退出
     */
    public function logout(){
        try {
            $this->guard()->logout();
            return showMsg('退出成功', 200);
        }catch(\Exception $e){
            return showMsg('退出失败');
        }
    }
}
