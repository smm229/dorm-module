<?php
namespace Modules\Dorm\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\Dorm\Entities\DormitoryAuthGroup;
use Modules\Dorm\Entities\DormitoryAuthGroupRules;
use Modules\Dorm\Entities\DormitoryAuthRule;
use Modules\Dorm\Entities\DormitoryAuthUser;
use App\Traits\SerializeDate;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\VarDumper\Dumper\esc;

/**
 * 角色组
 * Class AuthGroupController
 * @package App\Api\Controllers\V1
 */
class AuthGroupController extends Controller{
    use SerializeDate;
    use Helpers;

    /**
     * 角色组列表
     * @param Request $request
     */
    public function lists(Request $request){
        $list = DormitoryAuthGroup::get()->toArray();

        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $list]);
    }

    /**
     * 添加角色组
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request){
        try {
            $created_person = auth()->user()->username;
        }catch (\Exception $e) {
            return $this->response->error('登录失效',201);
        }
        if(!$request->rolename){
            return $this->response->error('角色名称必填',201);
        }

        $data = $request->only('rolename', 'describe','isall');

        if ($created_person) {
            $data['created_person'] = $created_person;
        }

        $info = DormitoryAuthGroup::where(['rolename'=>$request->rolename])->first();
        if($info) return $this->response->error('角色名称重复',201);
        $id =  DormitoryAuthGroup::insertGetId($data);

        $data['menuids'] = $request->menuids;
        $data['id'] = $id;
        DB::transaction(function () use ($data) {

            if ($data['menuids']) {
                if (!is_array($data['menuids'])) {
                    $data['menuids'] = json_decode($data['menuids'], true);
                }

                array_walk($data['menuids'], function ($v) use ($data) {
                    DormitoryAuthGroupRules::insertGetId(['roleid' => $data['id'], 'menuid' => $v]);
                }) ;
                $rules = implode(',',$data['menuids']);
                DormitoryAuthGroup::whereId($data['id'])->update(['rules'=>$rules]);
            }
        });

        return $this->response->array(['status_code' => 200, 'message'=> '成功']);

    }

    /**
     * 修改权限组
     * @param Request $request
     */
    public function edit(Request $request) {

        try {
            $created_person = auth()->user()->username;
        }catch (\Exception $e) {
            return $this->response->error('登录失效',201);
        }

        if (!$request->id) {
            return $this->response->error('参数不全',201);
        }

        $AuthGroup =  DormitoryAuthGroup::whereId($request->id)->first();
        $roleids = DormitoryAuthUser::whereUserid(auth()->user()->id)->pluck('roleid');
        $user = DormitoryAuthGroup::whereId($roleids)->first();
        if($AuthGroup['rules'] == "*" && $user['rules'] != "*"){
            return $this->response->error('权限不足，不可修改超管',201);
        }

        if(!$request->rolename){
            return $this->response->error('角色名称必填',201);
        }

        $result = DormitoryAuthGroup::where('rolename', $request->rolename)->first();
        if ($result && $result->id != $request->id) {
            return $this->response->error('应用名称重复',201);
        }

        $data = $request->only('rolename', 'describe','isall');

        if ($created_person) {
            $data['created_person'] = $created_person;
        }
        DormitoryAuthGroup::whereId($request->id)->update($data);

        $data['menuids'] = $request->menuids;
        $data['id'] = $request->id;
        DB::transaction(function () use ($data) {
            DormitoryAuthGroupRules::where(['roleid' => $data['id']])->delete();
            if ($data['menuids']) {
                if (!is_array($data['menuids'])) {
                    $data['menuids'] = json_decode($data['menuids'], true);
                }

                array_walk($data['menuids'], function ($v) use ($data) {
                    DormitoryAuthGroupRules::insertGetId(['roleid' => $data['id'], 'menuid' => $v]);
                }) ;
                $rules = implode(',',$data['menuids']);
                DormitoryAuthGroup::whereId($data['id'])->update(['rules'=>$rules]);
            }
        });

        return $this->response->array(['status_code' => 200, 'message'=> '成功']);
    }


    /**
     * 删除
     * @param Request $request
     */
    public function del(Request $request){

        $roleid = $request->id;
        if(!$roleid){
            return $this->response->error('请选择角色组',201);
        }
        $AuthGroup =  DormitoryAuthGroup::whereId($request->id)->first();
        if($AuthGroup['rules'] == "*"){
            return $this->response->error('超级管理员不可删除',201);
        }
        try{
            DB::transaction(function () use ($roleid){
                DormitoryAuthUser::whereRoleid($roleid)->delete();
                DormitoryAuthGroupRules::whereRoleid($roleid)->delete();
                DormitoryAuthGroup::whereId($roleid)->delete();
            });
            return $this->response->array(['status_code' => 200, 'message'=> '成功']);
        }catch(\Exception $e){
            return $this->response->error($e->getMessage(),201);
        }
    }

    /**
     * 角色详情
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function info(Request $request){
        if (!$request->id) {
            return $this->response->error('参数不全',201);
        }

        $AuthGroup =  DormitoryAuthGroup::whereId($request->id)->first();
        $roleids = DormitoryAuthUser::whereUserid(auth()->user()->id)->pluck('roleid');
        $user = DormitoryAuthGroup::whereId($roleids)->first();
        if($AuthGroup['rules'] == "*" && $user['rules'] != "*"){
            return $this->response->error('权限不足',201);
        }
        $list = DormitoryAuthRule::where(['type' => 1, 'disable' => 0])->orderBy('sort', 'asc')->get()->toArray();
        if($AuthGroup->rules == "*"){
            $arr = ['selected' => true];
            array_walk($list, function (&$value, $key, $arr) {
                $value = array_merge($value, $arr);
            },$arr);
            $list = listToTree($list);
        }else{
            $rules = explode(',',$AuthGroup['rules']);
            $clist = DormitoryAuthRule::where(['type' => 1, 'disable' => 0])->whereIn('id', $rules)->orderBy('sort', 'asc')->get()->toArray();

            foreach ($list as $k=>$v){
                foreach ($clist as $key=>$val){
                    if($val['id'] == $v['id']){
                        $list[$k]['selected'] = true;
                    }else{
                        if(!isset($list[$k]['selected'])){
                            $list[$k]['selected'] = false;
                        }
                    }
                }
            }
            $list = listToTree($list);
        }
        $data['rolename'] = $AuthGroup->rolename;
        $data['describe'] = $AuthGroup->describe;
        $data['isall'] =  $AuthGroup->isall;
        $data['rules'] = $list;

        return $this->response->array(['status_code' => 200, 'message'=> '成功','data' => $data]);
    }

    /**
     * 查看当前用户有哪些菜单
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function menulist(Request $request){

        $userinfo = auth()->user();
        if (!isset($userinfo['id'])) {
            return showMsg('信息获取失败');
        }
        $roleids = DormitoryAuthUser::whereUserid($userinfo['id'])->pluck('roleid');
        $user = DormitoryAuthGroup::whereId($roleids)->first();

        if($user['rules'] == "*"){
            $list = DormitoryAuthRule::where(['type' => 1, 'disable' => 0])->orderBy('sort', 'asc')->get()->toArray();
            $list = listToTree($list);
        }else{
            $rules = explode(',',$user['rules']);
            $list = DormitoryAuthRule::where(['type' => 1, 'disable' => 0])->whereIn('id', $rules)->orderBy('sort', 'asc')->get()->toArray();

        }

        return $this->response->array(['status_code' => 200, 'message'=> '成功','data' => $list]);
    }
}
