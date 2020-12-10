<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use Helpers;
    /**
     * 获取人员大数据平台的分类信息
     * Display a listing of the resource.
     * @return Renderable
     */
    public function categoryList(Request $request)
    {
        if (!$request['ckey']) {
            return $this->response->error('请求参数不正确', 201);
        }
        $client = new Client();
        $host = config('whitelist.host');
        $request_url = $host[0].'/api/category/list';
        $response = $client->request('post', $request_url, [
            'form_params' => [
                'ckey' => $request['ckey']
            ]
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        if ($result['status_code'] != 200) {
            return $this->response->error('获取数据失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);
    }

    /**
     * 获取人员大数据的教师列表
     * @param Request $request
     */
    public function teacherList(Request $request)
    {
        $client = new Client();
        $host = config('whitelist.host');
        $request_url = $host[0].'/api/teacher/list';
        $form_params = [
            'form_params' => [
                'positionid'   => $request['positionid'],
                'departmentid' => $request['departmentid'],
                'page'         => $request['page'],
                'search'       => $request['search'],
            ],
        ];
        $response = $client->request('post', $request_url, $form_params);
        $result = json_decode($response->getBody()->getContents(), true);
        if ($result['status_code'] != 200) {
            return $this->response->error('获取数据失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);
    }

    /**
     * 添加管理员账号
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request)
    {
        $client = new Client();
        $host = config('whitelist.host');
        $request_url = $host[0].'/api/teacher/list';
        $form_params = [
            'form_params' => [
               'idnumStr' => $request['idnumStr']
            ],
        ];
        $response = $client->request('post', $request_url, $form_params);
        $result = json_decode($response->getBody()->getContents(), true);
        if ($result['data']['data']) {
            $data = $result['data']['data'];
            foreach ($data as $k => $v) {
                $data[$k] = array_diff_key($v, ['id' =>'', 'ID_number' => '', 'ename' => '', 'nation' => '', 'positionid' => '', 'departmentid' => '', 'positionname' => '', 'departmentname' => '', 'created_at' => '', 'updated_at' => '']);
                $data[$k]['password'] = bcrypt('123456');
            }
        }
        $res = DormitoryUsers::insert($data);
        if (!$res) {
            return $this->response->error('添加失败,请联系管理员',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);
    }

    /**
     * 获取管理员列表
     */
    public function adminList(Request $request) {
        $res = DormitoryUsers::where(function ($req) use ($request){
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
        })->orderBy('id', 'desc')->paginate($request['pageSize']);
        if (!$res) {
            return $this->response->error('获取管理员失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 宿管管理员禁止登陆
     * @param Request $request
     */
    public function delete(Request $request)
    {
        if (!$request['id']) {
            return $this->response->error('获取数据失败',201);
        }
        $data = ['disable' => 1];
        $res = DormitoryUsers::where('id', $request['id'])->update($data);
        if ($res) {
            return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
        }
        return $this->response->error('禁用管理员失败',201);
    }

    /**
     * 管理员分配宿舍
     * @param Request $request
     */
    public function bindDorm(Request $request)
    {
        if (!$request['idnum']) {
            return $this->response->error('教师工号错误',201);
        }
        $idnum = $request['idnum'];
        $idnumRes = DormitoryUsers::where('idnum', $request['idnum'])->exists();
        if ($idnumRes != true) {
            return $this->response->error('教师信息错误',201);
        }
        //首先删除教师所控的宿舍楼
        $Res = DormitoryUsersBuilding::where('idnum', $request['idnum'])->exists();
        if ($Res) {
            $delRes = DormitoryUsersBuilding::where('idnum', $request['idnum'])->delete();
        }
        $buildid = $request['buildid'];
        $buildidArr = explode(',', $buildid);
        $buildidArrs = [];
        foreach ($buildidArr as $k => $v) {
            $buildidArrs[$k]['idnum']   = $idnum;
            $buildidArrs[$k]['buildid'] = $v;
        }
        $res = DormitoryUsersBuilding::insert($buildidArrs);
        if (!$res) {
            return $this->response->error('添加数据失败',201);
        }
        if ($res) {
            return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
        }
    }

    public function test() {
        $timestamp = time().'000';
    }
}
