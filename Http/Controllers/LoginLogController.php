<?php
namespace Modules\Dorm\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Dorm\Entities\DormitoryLoginlog;

class LoginLogController extends Controller{
    use Helpers;
    /**
     * 登录日志列表
     * @param Request $request
     */
    public function lists(Request $request){

        $res = DormitoryLoginlog::where(function ($req) use ($request){
            //模糊查询
            $title = false;
            if ($request['search']) {
                $search = $request['search'];
                if (is_numeric($search)) {
                    $title = 'idnum';
                } else {
                    $title = 'username';
                }
            }
            if ($title) $req->where($title, 'like', "%$search%");
        })->orderBy('id','desc')->paginate($request['pageSize']);

        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }
}
