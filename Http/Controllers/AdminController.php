<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Department;
use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use Modules\Dorm\Entities\DormitoryAdminlog;
use Modules\Dorm\Entities\DormitoryAuthUser;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Http\Requests\PasswordValidate;
use phpDocumentor\Reflection\Types\Integer;

class AdminController extends Controller
{
    use Helpers;

    protected $guard = 'dorm';
    /**
     * 添加子管理员账号
     * 可批量添加
     */
    public function create(Request $request)
    {
        if (!$request['idnumStr']) {
            return $this->response->error('参数错误',201);
        }

        if(!$request['roleid']){
            return $this->response->error('角色必填',201);
        }
        $idnumArr = explode(',', $request['idnumStr']);
        $result = Teacher::whereIn('idnum', $idnumArr)->get()->toArray();
        $results = [];
        if ($result) {
            foreach ($result as $k => $v) {
                $results[$k] = array_intersect_key($v, ['idnum' => '', 'username' => '', 'sex' => '', 'mobile' => '', 'headimg' => '']);
                if(!$request['password']){
                    $results[$k]['password'] = bcrypt('123456');
                }else{
                    $results[$k]['password'] = bcrypt($request['password']);
                }
            }
        }

        try {
            $data = [];
            DB::transaction(function () use ($results, &$data, $request) {

                foreach ($results as $k => $v) {
                    $id = DormitoryUsers::insertGetId($v);
                    $data[$k]['userid'] = $id;
                    $data[$k]['roleid'] = $request['roleid'];
                }

                DormitoryAuthUser::insert($data);
            });

            return $this->response->array(['status_code' => 200, 'message'=> '成功']);
        }catch (\Exception $e) {

            return $this->response->error('添加失败,请联系管理员',201);
        }

    }

    /**
     * 获取管理员列表
     */
    public function lists(Request $request) {
        $res = DormitoryUsers::leftjoin('personnel_teacher','personnel_teacher.idnum','=','dormitory_users.idnum')
            ->select('dormitory_users.*')
            ->where(function ($req) use ($request){
                if($request->campusid) $req->where('personnel_teacher.campusid',$request->campusid);
                if($request->departmentid) $req->where('personnel_teacher.departmentid',$request->departmentid);
                if($request->position) $req->where('personnel_teacher.position',$request->position);
                if($request->idnum) $req->where('personnel_teacher.idnum',$request->idnum);
                if ($request->username) $req->where('username', $request->username);
            })
            ->orderBy('id', 'desc')
            ->paginate($request['pageSize']);
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 宿管管理员禁止or开放登陆
     * @param Request $request
     */
    public function editstatus(Request $request)
    {
        if (!$request['id'] || is_null($request['disable']) || !(integer)$request['disable'] > 1) {
            return $this->response->error('缺少必填参数',201);
        }
        $data = ['disable' => $request['disable']];
        $res = DormitoryUsers::where('id', $request['id'])->update($data);
        if ($res) {
            return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
        }
        return $this->response->error('禁用管理员失败',201);
    }


    /**
     * 修改管理员
     * @param Request $request
     */
    public function edit(Request $request){

        if (!$request->id || !$request->roleid){
            return showMsg('缺少参数');
        }
        $data = $request->only('id', 'password', 'roleid');

        $info = DormitoryUsers::whereId($data['id'])->first();
        if (!$info) {
            return showMsg('信息错误');
        }
        try {

            DB::transaction(function () use ($data){
                $list = [];
                if(isset($data['password'])) $list['password'] = bcrypt($data['password']);
                DormitoryUsers::whereId($data['id'])->update($list);
                DormitoryAuthUser::where('userid', $data['id'])->delete();
                DormitoryAuthUser::insertGetId(['userid' => $data['id'], 'roleid' => $data['roleid']]);
                });

            return $this->response->array(['status_code' => 200, 'message'=> '成功']);
        }catch(\Exception $e){
            return $this->response->error('失败',201);
        }

    }

    /**
     * 删除管理员
     * @param Request $request
     */
    public function del(Request $request){
        $user = DormitoryUsers::whereId($request->id)->first();
        if(!$user){
            return $this->response->error('用户不存在',201);
        }
        //TODO 超级管理员id为1 不要任意修改
        if($request->id == 1){
            return $this->response->error('超级管理员无法删除',201);
        }
        if($request->id == auth()->user()->id){
            return $this->response->error('无法删除自己',201);
        }

        try{
            DormitoryUsers::whereId($request->id)->forceDelete();
            DormitoryAuthUser::where('userid', $request->id)->delete();
            return $this->response->array(['status_code' => 200, 'message'=> '成功']);
        }catch(\Exception $e){
            return $this->response->error('失败',201);
        }

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


    /*
     * 修改密码
     * @param password 旧密码
     * @param newpassword 新密码
     * @param repassword 重复密码
     */
    public function changePwd(PasswordValidate $request) {
        $user = DormitoryUsers::find(auth($this->guard)->user()->id);
        if (!$user){
            return showMsg('用户不存在');
        }
        if(!password_verify($request->password , $user->password )){
            return showMsg('密码输入有误，请重新输入');
        }
        if($request->newpassword != $request->repassword){
            return showMsg('两次密码输入不一致');
        }
        $user->password = bcrypt($request->newpassword);
        $user->save();
        return showMsg('修改成功',200);
    }


    /**
     * 编辑系统设置
     * @param Request $request
     */
    public function setSysconfig(Request $request)
    {
        if (!$request['key'] || !$request['value']) {
            return $this->response->error('参数错误',201);
        }
        $update = [
            'value' => $request['value'],
        ];
        $res = DB::table('dormitory_config')->where('key', $request['key'])->update($update);
        if (!$res && !empty($res)) {
            return $this->response->error('编辑失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 获取系统设置
     * @param Request $request
     */
    public function getSysconfig(Request $request)
    {
        $res = DB::table('dormitory_config')->where(function($q) use ($request) {
            if ($request['key']) $q->where('key', $request['key']);
        })->get();
        if (!$res && !empty($res)) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 管理员操作日志
     * @param Request $request
     */
    public function getAadminlog(Request $request){
        if (!$request['idnum']) {
            return $this->response->error('参数错误',201);
        }
        $res = DormitoryAdminlog::where('idnum',$request['idnum'])->orderBy('id','desc')->paginate($request['pageSize']);
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }


}
