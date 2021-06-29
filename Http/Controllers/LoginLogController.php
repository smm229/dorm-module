<?php
namespace Modules\Dorm\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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

            if($request['start_date']) $req->where('created_at','>=',$request['start_date']);
            if($request['end_date']) $req->where('created_at','<=',$request['end_date']);
        })->orderBy('id','desc')->paginate($request['pageSize']);

        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        foreach ($res as $k =>$v){
            $res[$k]['group'] = DB::table('dormitory_auth_user')
                                    ->join('dormitory_auth_group','dormitory_auth_group.id','=','dormitory_auth_user.roleid')
                                    ->where('dormitory_auth_user.userid',$v['user_id'])
                                    ->value('dormitory_auth_group.rolename');

        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }
}
