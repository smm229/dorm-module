<?php

namespace Modules\Dorm\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Dorm\Entities\DormitoryLoginlog;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Http\Requests\LoginValidate;
use Zhuzhichao\IpLocationZh\Ip;



class AuthController extends Controller
{
    protected $guard = "dorm";

    /**
     * 发送数据
     * @param String $url     请求的地址
     * @param Array  $header  自定义的header数据
     * $header = array('x:y','language:zh','region:GZ');
     * @param Array  $content POST的数据
     * $content = array('name' => 'wumian');
     * @param Array  $backHeader 返回数据是否返回header
     * 0不反回 1返回
     * @param Array  $cookie 携带的cookie
     * @return String
     */
    function tocurl($url, $header, $content=array(),$backHeader=0,$cookie=''){
        $ch = curl_init();
        if(!isset($header[0])){//将索引数组转为键值数组
            foreach($header as $hk=>$hv){
                unset($header[$hk]);
                $header[]=$hk.':'.$hv;
            }
        }
        curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, false);
        if(count($content)){
            curl_setopt($ch, CURLOPT_POSTFIELDS,$content);
        }
        if(!empty($cookie)){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($ch, CURLOPT_HEADER,$backHeader); // 显示返回的Header区域内容
        $response = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 测试login
     */
    public function test(){
        $senselink = new \senselink();

        try{
            $rsa = $senselink->getRsa();
            file_put_contents(storage_path('logs/sylogin.log'),'rsa:'.json_encode($rsa).PHP_EOL,FILE_APPEND);
            //$rsa = '{"module":"c53c5f2d31fa5fffe9b6af2b0eb3c9281a421c1200c8a71cb41583a5d72deb493b35a84b80134568e015321b4d839cdb95626a6a0025dd48d0afcc3172ba1acef62902bf9050988633b6cb02b57f5601298a7f883e961c5edfa614f16fb1ce0fe0b03da9f0eac43be566109fb6bc67d1ad4880cbb1b148486411dae4779dd647","empoent":"10001","rsa_id":"slkv2-rsaid-nQzP2a5GRzBbkeJd"}';
            //$rsa = json_decode($rsa,true);

            $rsat = new \Crypt_RSA();

            $modulus = new \Math_BigInteger($rsa['module'], 16);

            $exponent = new \Math_BigInteger($rsa['empoent'], 16);

            $rsat->loadKey(array('n' => $modulus, 'e' => $exponent));
            //$rsat->setPublicKey(array('n' => $modulus, 'e' => $exponent));
            //$rsat->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);

            echo $rsat->getPublicKey();exit;
            $ciphertext = $rsat->encrypt('hnrt123456');
            $password = bin2hex($ciphertext);

            $authtoken = $senselink->syncLogin($password,$rsa['rsa_id']);

        }catch(\Exception $e){
            dd($e->getLine().$e->getMessage());
        }
        dd($authtoken);
    }

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

            $data = [
                'city' => $city[2],
                'idnum'=> $user->idnum,
                'ip'=>$ip,
                'username'=>$user->username,
                'user_id' =>$user->id
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
