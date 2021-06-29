<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use App\Extend\SenseNebula;
use App\Jobs\SyncNebula;
use App\Models\Student;
use App\Models\Teacher;
use Egulias\EmailValidator\EmailLexer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Dorm\Entities\DormitoryBlack;
use Modules\Dorm\Entities\DormitoryBlackAccessRecord;
use Modules\Dorm\Entities\DormitoryBlackGroup;
use Modules\Dorm\Entities\DormitoryCategory;
use Modules\Dorm\Entities\DormitoryGuest;
use Modules\Dorm\Entities\DormitoryNebula;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use Modules\Dorm\Http\Requests\DormBlackValidate;
use Modules\Dorm\Jobs\SenseNebulaBlackAdd;
use senselink;

class DormBlackController extends Controller
{

    public function __construct()
    {
        $this->senselink = new senselink();
    }



    /**
     * 黑名单列表
     * @param Request $request
     */
    public function lists(Request $request){

        $res = DormitoryBlack::where(function ($req) use ($request){
            if ($request['username']) $req->where('username', $request['username']);
            if ($request['blacklist_type']) $req->where('blacklist_type', $request['blacklist_type']);
            if ($request['type']) $req->where('type', $request['type']);
            if ($request['blacklist_class']) $req->where('blacklist_class', $request['blacklist_class']);
        })->orderBy('id','desc')->paginate($request['pageSize'])->toArray();

        if (!$res) {
            return showMsg('获取失败');
        }
        return showMsg('',200,$res);
    }

    /**
     * 添加黑名单
     * @param DormBlackValidate $request
     *   * @param type 类型
     * @param headimg 头像
     * @param username 姓名
     * @param sex 性别1男2女
     */
    public function add(DormBlackValidate $request)
    {
        if($request['type'] == 1){

            $senselink_id =  Student::where('id',$request['id'])->value('senselink_id');

        }elseif ($request['type'] == 2 || $request['type'] == 3){

            $senselink_id =  Teacher::where('id',$request['id'])->value('senselink_id');
        }elseif ($request['type'] == 4 ){

            $senselink_id =  DormitoryGuest::where('id',$request['id'])->value('link_id');
        }else{
            //检查图片是否合理
            $ext = substr(strrchr($request->headimg,'.'),1);
            $arr = [
                'path'  =>  public_path($request->headimg),
                'type'  =>  $ext
            ];
            $linkRes_headimg = $this->senselink->linkheadimg($arr);
            if ($linkRes_headimg['code'] == 200 && $linkRes_headimg['message'] == 'OK') {

                //处理人员头像信息
                $headimg['path'] = public_path($request['headimg']);
                $imagetype = substr(strrchr($headimg['path'],'.'),1);
                if (strtolower($imagetype) == 'jpg') {
                    $imagetype = 'jpeg';
                }
                $headimg['imgtype'] = 'image/'.$imagetype;
                //加入link
                $result_link = $this->senselink->linkperson_add($request['username'], $headimg, '', '','','', '', '', $request['ID_number']);
                if ($result_link['code'] == 30002) {
                    return showMsg('存在相似的人员');
                }

                if($result_link['code'] == 200 && $result_link['message'] == 'OK'){
                    $senselink_id = $result_link['data']['id'];
                }else{
                    return showMsg('添加失败');
                }
            }else {
                return showMsg('图片不符合要求');
            }
        }
        DB::beginTransaction();
        try {

            $black_group  = env('LIKEGROUP_BLACKLIST');
            //移入link黑名单
            $movein_link = $this->senselink->linkblacklist_movein($senselink_id,$black_group);

            if($movein_link['code'] == 200){
                if($request['type'] == 1){

                    Student::where('id',$request['id'])->update(['senselink_id'=>null]);
                    $user_id = $request['id'];
                }elseif ($request['type'] == 2 || $request['type'] == 3){

                    Teacher::where('id',$request['id'])->update(['senselink_id'=>null]);
                    $user_id = $request['id'];
                }elseif ($request['type'] == 4 ){

                    DormitoryGuest::where('id',$request['id'])->update(['link_id'=>null]);
                    $user_id = $request['id'];
                }else{
                    $user_id = 0;
                }

                $data = $request->only(['username','headimg','sex','type','nation','ID_number','blacklist_type','blacklist_reason','blacklist_class']);

                if(empty($data['blacklist_class']) || !isset($data['blacklist_class'])){
                    $data['blacklist_class'] = 35;
                }

                $data['senselink_id'] = $senselink_id;
                $data['author'] = auth()->user()->username;
                $id =  DormitoryBlack::insertGetId($data);

                DormitoryBlackGroup::insertGetId(['type'=>$request['type'],'senselink_id'=>$data['senselink_id'],'user_id'=>$user_id]);
                DB::commit();
            }else{
                DB::rollBack();
                return showMsg('添加失败');
            }
        }catch(\Exception $e) {
            DB::rollback();
            return showMsg('添加失败');
        }

        if($data['blacklist_class'] == 33){
            $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
        }elseif ($data['blacklist_class'] == 34){
            $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
        }else{
            $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
        }
        Queue::push(new SyncNebula([$id],4,$lib_id,1));
        return showMsg('添加成功',200);
    }

    /**
     * 黑名单详情
     * @param Request $request
     */
    public function info(Request $request){

        if(!$request['id']){
            return showMsg('参数错误');
        }

        $black = DormitoryBlack::where('id',$request['id'])->first()->toArray();

        return showMsg('成功',200,$black);
    }

    /**
     * 黑名单人员修改
     * @param DormBlackValidate $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(DormBlackValidate $request){
        if(!$request['id']){
            return showMsg('参数错误');
        }

        $black = DormitoryBlack::where('id',$request['id'])->first()->toArray();
        if($request['type'] == 5){
            $ext = substr(strrchr($request->headimg,'.'),1);
            $arr = [
                'path'  =>  public_path($request->headimg),
                'type'  =>  $ext
            ];
            $linkRes_headimg = $this->senselink->linkheadimg($arr);   //验证图片信息
            if ($linkRes_headimg['code'] == 200 && $linkRes_headimg['message'] == 'OK') {
                //处理人员头像信息
                $headimg['path'] = public_path($request['headimg']);
                $imagetype = substr(strrchr($headimg['path'],'.'),1);
                if (strtolower($imagetype) == 'jpg') {
                    $imagetype = 'jpeg';
                }
                $headimg['imgtype'] = 'image/'.$imagetype;
                $result_link = $this->senselink->linkperson_add($request['username'], $headimg, '', '','','', '', '', $request['ID_number']); //加入link
                if ($result_link['code'] == 30002) {
                   if($result_link['data']['similar_user_id'] != $black['senselink_id']){
                       return showMsg('存在相似的人员');
                   }else{
                       $data = $request->only(['username','headimg','sex','type','nation','ID_number','blacklist_type','blacklist_reason','blacklist_class']);

                       $result_update = $this->senselink->linkblacklist_update($black['senselink_id'],$request['username'],$headimg,$request['ID_number']);

                       if($result_update['code'] == 200 && $result_update['message'] == 'OK'){
                           if(empty($data['blacklist_class']) || !isset($data['blacklist_class'])){
                               $data['blacklist_class'] = 35;
                           }
                           $data['author'] = auth()->user()->username;
                           DormitoryBlack::where('id', $request['id'])->update($data);

                           if($data['blacklist_class'] == 33){
                               $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                           }elseif ($data['blacklist_class'] == 34){
                               $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                           }else{
                               $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                           }

                           Queue::push(new SyncNebula([$request['id']],4,$lib_id,2));
                           return showMsg('修改成功',200);
                       }
                   }
                }

                if($result_link['code'] == 200 && $result_link['message'] == 'OK'){

                    $senselink_id = $result_link['data']['id'];
                    $black_group  = env('LIKEGROUP_BLACKLIST');
                    $movein_link = $this->senselink->linkblacklist_movein($senselink_id,$black_group); //移入link黑名单

                    if($movein_link['code'] == 200 && $movein_link['message'] == 'OK'){

                        $del_link =  $this->senselink->linkblacklist_del($black['senselink_id']); //删除老数据

                        //星云删除
                        $info =   DormitoryBlack::find($request['id']);
                        $img_ids = $info->img_id ? unserialize($info->img_id) : [];
                        if($info->blacklist_class == 33){
                            $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                        }elseif ($info->blacklist_class == 34){
                            $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                        }else{
                            $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                        }
                        Queue::push(new SyncNebula($img_ids,4,$lib_id,3));

                        if($del_link['code'] == 200 && $del_link['message'] == 'OK'){

                            DormitoryBlack::where('id',$request['id'])->delete();
                            DormitoryBlackGroup::where('senselink_id',$black['senselink_id'])->delete();

                            $data = $request->only(['username','headimg','sex','type','nation','ID_number','blacklist_type','blacklist_reason','blacklist_class']);

                            if(empty($data['blacklist_class']) || !isset($data['blacklist_class'])){
                                $data['blacklist_class'] = 35;
                            }
                            $data['senselink_id'] = $senselink_id;
                            $data['author'] = auth()->user()->username;
                            $id =  DormitoryBlack::insertGetId($data);

                            DormitoryBlackGroup::insertGetId(['type'=>$request['type'],'senselink_id'=>$senselink_id]);

                            //添加新的星云
                            if($data['blacklist_class'] == 33){
                                $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                            }elseif ($data['blacklist_class'] == 34){
                                $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                            }else{
                                $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                            }
                            Queue::push(new SyncNebula([$id],4,$lib_id,1));
                            return showMsg('修改成功',200);
                        }
                    }
                }
            }
            return showMsg('修改失败');
        }else{
            try {
                if (isset($request['user_id'])) { //更换黑名单人员

                    $result_link = $this->senselink->linkblacklist_moveout($black['senselink_id']);

                    if($result_link['code'] == 200 && $result_link['message'] == 'OK'){   //移除当前黑名单用户

                        $group =   DormitoryUsersGroup::where('senselink_id',$black['senselink_id'])->pluck('groupid')->toArray();
                        $group =   implode(',', $group);
                        $linkperson_edit =  $this->senselink->linkperson_edit($black['senselink_id'],'','',$group);
                        if($linkperson_edit['code'] == 200 && $linkperson_edit['message'] == 'OK'){  // 重新编辑员工组

                            if($request['type'] == 1){

                                $senselink_id =  Student::where('id',$request['user_id'])->value('senselink_id');

                            }elseif ($request['type'] == 2 || $request['type'] == 3){

                                $senselink_id =  Teacher::where('id',$request['user_id'])->value('senselink_id');
                            }else{

                                $senselink_id = DormitoryGuest::where('id', $request['user_id'])->value('link_id');
                            }

                            $black_group  = env('LIKEGROUP_BLACKLIST');
                            //移入link黑名单
                            $movein_link = $this->senselink->linkblacklist_movein($senselink_id,$black_group);
                            if($movein_link['code'] == 200 && $movein_link['message'] == 'OK'){
                                $data = $request->only(['username','headimg','sex','type','nation','ID_number','blacklist_type','blacklist_reason','blacklist_class']);
                                $data['senselink_id'] = $senselink_id;
                                $data['author'] = auth()->user()->username;
                                if(empty($data['blacklist_class']) || !isset($data['blacklist_class'])){
                                    $data['blacklist_class'] = 35;
                                }
                                DormitoryBlack::where('id', $request['id'])->update($data);

                                if($request['type'] == 1){

                                    $user_id =   DormitoryBlackGroup::where(['type' =>$black['type'] ,'senselink_id' => $black['senselink_id']])->value('user_id');
                                    Student::where('id',$user_id)->update(['senselink_id'=>$black['senselink_id']]);
                                }elseif ($request['type'] == 2 || $request['type'] == 3){

                                   $user_id =  DormitoryBlackGroup::where(['type' =>$black['type'] ,'senselink_id' => $black['senselink_id']])->value('user_id');
                                    Teacher::where('id',$user_id)->update(['senselink_id'=>$black['senselink_id']]);
                                }elseif ($request['type'] == 4 ){
                                    $user_id =  DormitoryBlackGroup::where(['type' =>$black['type'] ,'senselink_id' => $black['senselink_id']])->value('user_id');
                                    DormitoryGuest::where('id',$user_id)->update(['link_id'=>$black['senselink_id']]);
                                }

                                DormitoryBlackGroup::where(['type' =>$black['type'] ,'senselink_id' => $black['senselink_id']])->update(['type'=>$request['type'],'senselink_id'=>$senselink_id,'user_id'=>$request['user_id']]);

                                if($data['blacklist_class'] == 33){
                                    $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                                }elseif ($data['blacklist_class'] == 34){
                                    $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                                }else{
                                    $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                                }
                                Queue::push(new SyncNebula([$request['id']],4,$lib_id,2));
                            }else{
                                return showMsg('修改失败');
                            }

                        }else{
                            return showMsg('修改失败');
                        }

                    }else{
                        return showMsg('修改失败');
                    }

                } else {  //没有更换黑名单人员

                    $data = $request->only(['blacklist_type', 'blacklist_reason', 'blacklist_class']);
                    if(empty($data['blacklist_class']) || !isset($data['blacklist_class'])){
                        $data['blacklist_class'] = 35;
                    }
                    $data['author'] = auth()->user()->username;
                    DormitoryBlack::where('id', $request['id'])->update($data);

                    if($data['blacklist_class'] == 33){
                        $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                    }elseif ($data['blacklist_class'] == 34){
                        $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                    }else{
                        $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                    }
                    Queue::push(new SyncNebula([$request['id']],4,$lib_id,2));
                }
            }catch(\Exception $e) {
                return showMsg('修改失败');
            }
            return showMsg('修改成功',200);
        }
    }

    /**
     * 黑名单删除
     * @param Request $request
     */
    public function delete(Request $request){


        if(!$request['id']){
            return showMsg('参数错误');
        }
        DB::beginTransaction();
        try {

            $DormitoryBlack = DormitoryBlack::where('id',$request['id'])->first()->toArray();
            if ($DormitoryBlack['type'] == 5){
                $result_link =  $this->senselink->linkblacklist_del($DormitoryBlack['senselink_id']);
            }else{
                $result_link = $this->senselink->linkblacklist_moveout($DormitoryBlack['senselink_id']);

                if($result_link['code'] == 200 && $result_link['message'] == 'OK'){

                    $group =   DormitoryUsersGroup::where('senselink_id',$DormitoryBlack['senselink_id'])->pluck('groupid')->toArray();
                    $group =   implode(',', $group);
                    $result_link =  $this->senselink->linkperson_edit($DormitoryBlack['senselink_id'],'','',$group);
                }else{
                    return showMsg('删除失败');
                }
            }

            if($result_link['code'] == 200 && $result_link['message'] == 'OK'){

                if($DormitoryBlack['type'] == 1){

                    $user_id =   DormitoryBlackGroup::where(['type' =>$DormitoryBlack['type'] ,'senselink_id' => $DormitoryBlack['senselink_id']])->value('user_id');
                    Student::where('id',$user_id)->update(['senselink_id'=>$DormitoryBlack['senselink_id']]);
                }elseif ($DormitoryBlack['type'] == 2 || $DormitoryBlack['type'] == 3){

                    $user_id =  DormitoryBlackGroup::where(['type' =>$DormitoryBlack['type'] ,'senselink_id' => $DormitoryBlack['senselink_id']])->value('user_id');
                    Teacher::where('id',$user_id)->update(['senselink_id'=>$DormitoryBlack['senselink_id']]);
                }elseif ($DormitoryBlack['type'] == 4 ){
                    $user_id =  DormitoryBlackGroup::where(['type' =>$DormitoryBlack['type'] ,'senselink_id' => $DormitoryBlack['senselink_id']])->value('user_id');
                    DormitoryGuest::where('id',$user_id)->update(['link_id'=>$DormitoryBlack['senselink_id']]);
                }

                DormitoryBlack::where('id',$request['id'])->delete();
                DormitoryBlackGroup::where('senselink_id',$DormitoryBlack['senselink_id'])->delete();
                DB::commit();

                $img_ids = $DormitoryBlack['img_id'] ? unserialize($DormitoryBlack['img_id']) : [];

                if($img_ids){
                    if($DormitoryBlack['blacklist_class'] == 33){
                        $lib_id = env('SENSE_NEBULA_POLICE_GROUP');
                    }elseif ($DormitoryBlack['blacklist_class'] == 34){
                        $lib_id = env('SENSE_NEBULA_SECURITY_GROUP');
                    }else{
                        $lib_id = env('SENSE_NEBULA_WARNING_GROUP');
                    }
                   Queue::push(new SyncNebula($img_ids,4,$lib_id,3));
                }

            }else{
                DB::rollBack();
                return showMsg('删除失败');
            }
        }catch(\Exception $e) {
            DB::rollback();
            return showMsg('删除失败');
        }
        return showMsg('删除成功',200);
    }


    /**
     * 导出
     * @param Request $request
     */
    public function export(Request $request){

        $data = DormitoryBlack::where(function ($req) use ($request){
            if ($request['username']) $req->where('username', $request['username']);
            if ($request['blacklist_type']) $req->where('blacklist_type', $request['blacklist_type']);
            if ($request['type']) $req->where('type', $request['type']);
            if ($request['blacklist_class']) $req->where('blacklist_class', $request['blacklist_class']);
        })->orderBy('id','desc')
            ->get()
            ->toArray();
        $header = [[

            'username'          =>  '姓名',
            'sex_name'          =>  '性别',
            'type_name'         =>  '类型',
            'nation'            =>  '名族',
            'ID_number'         =>  '身份证号',
            'blacklist_type_name'    =>  '黑名单类型',
            'blacklist_reason_name'  =>  '黑名单原因',
            'blacklist_class_name'   =>  '黑名单等级'
        ]];
        $excel = new Export($data, $header,'黑名单');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }



    /**
     * 获取未在黑名单人员列表
     * @param Request $request
     */
    public function blacklist(Request $request){

        if (!$request['type']) {
            return showMsg('参数错误');
        }

        $black_group = DormitoryBlackGroup::where('type',$request['type'])->pluck('senselink_id')->toArray();
        if($request['type'] == 1){

            $res = Student::where(function ($req) use ($request, $black_group) {
                $req->whereNotIn('senselink_id', $black_group);
                if ($request['grade'])     $req->where('grade', $request['grade']);
                if ($request['campusid'])  $req->where('campusid', $request['campusid']);
                if ($request['collegeid']) $req->where('collegeid', $request['collegeid']);
                if ($request['majorid'])   $req->where('majorid', $request['majorid']);
                if ($request['classid'])   $req->where('classid', $request['classid']);
                if ($request['idnum'])     $req->where('idnum', 'like', '%'.$request['idnum'].'%');
            })->orderBy('id', 'desc')->paginate($request['pagesize']);

        }elseif ($request['type'] == 2){

            $res =  Teacher::where(function ($req) use ($request, $black_group) {
                $req->whereNotIn('senselink_id', $black_group);

                if ($request['campusid']) $req->where('campusid', $request['campusid']);
                if ($request['departmentid']) $req->where('departmentid', $request['departmentid']);
                if ($request['position']) $req->where('position', $request['position']);
                if ($request['idnum']) $req->where('idnum', 'like', '%'.$request['idnum'].'%');
                if ($request['username']) $req->where('username', 'like', '%'.$request['username'].'%');

            })->where('type','1')->orderBy('id', 'desc')->paginate($request['pagesize']);

        }elseif ($request['type'] == 3){

            $res =  Teacher::where(function ($req) use ($request, $black_group) {
                $req->whereNotIn('senselink_id', $black_group);
                if ($request['search']) {
                    if (is_numeric($request['search'])) {
                            $req->where('ID_number', 'like', '%'.$request['search'].'%');
                    } else {
                            $req->where('username', 'like', '%'.$request['search'].'%');
                    }
                }
            })->where('type','3')->orderBy('id', 'desc')->paginate($request['pagesize']);

        }else{

            $res = DormitoryGuest::where(function ($req) use ($request, $black_group) {
                $req->whereNotIn('link_id', $black_group);
                if ($request['search']) {
                    if (is_numeric($request['search'])) {
                        $req->where('ID_number', 'like', '%'.$request['search'].'%');
                    } else {
                        $req->where('username', 'like', '%'.$request['search'].'%');
                    }
                }
            })->where('status','2')->orderBy('id', 'desc')->paginate($request['pagesize']);

        }

        return showMsg('',200, $res);
    }


    /**
     * 获取黑名单类型、原因、等级
     * @param Request $request
     */
    public function blacklistCategory(Request $request){

        $blacklist_type = DormitoryCategory::where('ckey','blacklist_type')->get();
        $blacklist_reason = DormitoryCategory::where('ckey','blacklist_reason')->get();
        $blacklist_class =  DormitoryCategory::where('ckey','blacklist_class')->get();

        return showMsg('',200, ['blacklist_type'=>$blacklist_type,'blacklist_reason'=>$blacklist_reason,'blacklist_class'=>$blacklist_class]);
    }

    /**
     * 添加黑名单类型、原因、等级
     * @param Request $request
     */
    public function categoryAdd(Request $request){

        if (!$request['name'] || !$request['ckey']) {
            return showMsg('参数错误');
        }

        if(DormitoryCategory::whereName($request->name)->where( 'ckey' ,$request['ckey'])->first()){
            return showMsg('名称已存在');
        }

        try {
            DormitoryCategory::insert([
                'name' => $request['name'],
                'ckey' => $request['ckey'],
                'sort' => $request['name'] ?? 1,
            ]);
            return showMsg('添加成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }
    }

    /**
     * 修改黑名单类型、原因、等级
     * @param Request $request
     */
    public function categoryEdit(Request $request){

        if (!$request['name'] || !$request['ckey'] || !$request['id']) {
            return showMsg('参数错误');
        }

        if(!$info = DormitoryCategory::find($request['id'])){
            return showMsg('信息不存在');
        }

        if($r=DormitoryCategory::whereName($request['name'])->where( 'ckey' ,$info['ckey'])->first()){
            if($r->id !=$request['id'])  return showMsg('名称已存在');
        }

        try {
            DormitoryCategory::whereId($request['id'])->update([
                'name' => $request['name'],
                'sort' => $request['name'] ?? 1,
            ]);
            return showMsg('编辑成功',200);
        }catch(\Exception $e){
            return showMsg('编辑失败');
        }
    }


    /**
     * 删除黑名单类型、原因、等级
     * @param Request $request
     */
    public function categoryDel(Request $request){

        if (!$request['id']) {
            return showMsg('参数错误');
        }

        try {
            DormitoryCategory::whereId($request['id'])->delete();
            return showMsg('删除成功',200);
        }catch(\Exception $e){
            return showMsg('删除失败');
        }
    }

    /**
     * 黑名单识别记录
     * @param Request $request
     */
    public function accessRecord(Request $request){

        $res = DormitoryBlackAccessRecord::where(function ($req) use ($request){
            if ($request['campusname']) $req->where('campusname', $request['campusname']);
            if ($request['buildid']) $req->where('buildid', $request['buildid']);
            if ($request['start_date']) $req->where('pass_time','>=',$request['start_date']);
            if ($request['end_date']) $req->where('pass_time','<=',$request['end_date'].' 23:59:59');
        })->orderBy('id','desc')->paginate($request['pageSize'])->toArray();

        if (!$res) {
            return showMsg('获取失败');
        }

        return showMsg('',200,$res);
    }

    /**
     * 黑名单识别记录导出
     * @param Request $request
     */
    public function accessExport(Request $request){

        $data = DormitoryBlackAccessRecord::where(function ($req) use ($request){
                if ($request['campusname']) $req->where('campusname', $request['campusname']);
                if ($request['buildid']) $req->where('buildid', $request['buildid']);
                if ($request['start_date']) $req->where('pass_time','>=',$request['start_date']);
                if ($request['end_date']) $req->where('pass_time','>=',$request['end_date'].' 23:59:59');
            })->orderBy('id','desc')
            ->get()
            ->toArray();
        $header = [[
            'username'         =>  '姓名',
            'sex'              =>  '性别',
            'campusname'       =>  '校区',
            'pass_location'    =>  '识别地点',
            'pass_way'         =>  '识别通道',
            'direction'        =>  '识别状态',
            'pass_time'        =>  '识别时间',
        ]];
        $excel = new Export($data, $header,'黑名单识别记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');

    }
}
