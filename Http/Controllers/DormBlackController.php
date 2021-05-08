<?php

namespace Modules\Dorm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Http\Requests\DormBlackValidate;
use senselink;

class DormBlackController extends Controller
{

    public function __construct()
    {
        $this->senselink = new senselink();
    }

    /**
     * 添加黑名单
     * @param DormBlackValidate $request
     * @param headimg 头像
     * @param username 姓名
     * @param sex 性别1男2女
     */
    public function add(DormBlackValidate $request)
    {
        //检查图片是否合理
        $ext = substr(strrchr($request->headimg,'.'),1);
        $arr = [
            'path'  =>  public_path($request->headimg),
            'type'  =>  $ext
        ];
        $res = $this->senselink->linkheadimg($arr);
        dd($res);
    }

}
