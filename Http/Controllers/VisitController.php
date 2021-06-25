<?php

namespace Modules\Dorm\Http\Controllers;

use App\Api\Controllers\V1\GuestController;
use App\Exports\Export;
use App\Jobs\SyncNebula;
use App\Models\Student;
use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryGuestAccessRecord;
use Modules\Dorm\Entities\Visit;
use Modules\Dorm\Http\Requests\VisitValidate;
use Modules\Dorm\Jobs\AllocateGuest;
use senselink;

class VisitController extends Controller
{

    use Helpers;
    public function __construct()
    {
        $this->senselink = new senselink();
    }

    /**
     * 添加访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function create(VisitValidate $request)
    {
        if(!is_mobile($request->mobile)){
            return showMsg('手机格式错误');
        }
        $visit_place = explode(',', $request['visit_place']);
        $groups = DB::table('dormitory_group')->whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
        $groups = implode(',', $groups);
        $headimg['path'] = public_path($request['headimg']);
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = $request['receptionUserId'];
        $headimg['imgtype'] = 'image/'.$imagetype;
        $result_link = $this->senselink->linkguest_add($request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups,'','','','',0);
        file_put_contents(storage_path('logs/11.log'),json_encode($result_link).PHP_EOL,FILE_APPEND);
        if ($result_link['code'] == 30002) {
            //如果link判断人员已存在，则先判断人员步骤如下：1 黑名单（暂时忽略） 2 学生 教职工 3 已存在的访客

            //2.判断学生or教师
            $stuInfo = Student::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($stuInfo == true) {
                return $this->response->error('此人员为校内学生, 无法添加为访客',201);
            }
            $teacherInfo = Teacher::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($teacherInfo == true) {
                return $this->response->error('此人员为校内教职工, 无法添加为访客',201);
            }
            file_put_contents(storage_path('logs/11.log'),json_encode($result_link).PHP_EOL,FILE_APPEND);
            $perInfo = Visit::where('link_id', $result_link['data']['similar_user_id'])->first();
            if ($perInfo) {
                //失效之前的访客记录
                $perInfo->status = 4;
                $perInfo->updated_at = date('Y-m-d H:i:s');
                $perInfo->save();
            }
            $result_links = $this->senselink->linkguest_del($result_link['data']['similar_user_id']);
            if ($result_links['code'] == 200) {
                //$result_link = $this->senselink->linkguest_add($request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
            }
        }elseif($result_link['code'] !=200){
            return showMsg('添加失败，'.$result_link['desc']);
        }
        try {
            $teacher = Teacher::where('senselink_id', $receptionUserId)->first();
            $addArr = [
                'username' => $request['username'],
                'campusid'  =>  $request->campusid,
                'headimg' => $request['headimg'],
                'sex' => $request['sex'],
                'begin_time' => $request['begin_time'],
                'end_time' => $request['end_time'],
                'visit_place' => $request['visit_place'],
                'receptionUserId' => $receptionUserId,
                'link_id' => $result_link['data']['id'],
                'mobile'    =>  $request->mobile ?? '',
                'ID_number' =>  $request->ID_number ??'',
                'visit_note' => $request->visit_note ?? '',
                'idnum'     =>  $teacher->idnum,
                'receptionuser'=>$teacher->username,
                'status' => 2,
                'confirm_time' => date('Y-m-d H:i:s')
            ];
            $res = Visit::insertGetId($addArr);
            GuestController::pushdata($addArr);
            Queue::push(new SyncNebula([$res],3,env('SENSE_NEBULA_WHITE_GROUP') ?? 1,1));
            return $this->response->array(['status_code' => 200, 'message' => '成功', 'data' => $res]);
        }catch(\Exception $e){
            return showMsg($e->getMessage());
        }
    }

    /**
     * 编辑访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function edit(VisitValidate $request)
    {
        if(!is_mobile($request->mobile)){
            return showMsg('手机格式错误');
        }
        $perInfo = Visit::where('id', $request['id'])->get()->toArray();
        if (!$perInfo) {
            return $this->response->error('编辑失败',201);
        }
        if($perInfo[0]['status']!=1){
            return showMsg('当前状态不可操作，请重新添加');
        }
        $visit_place = explode(',', $request['visit_place']);
        //$groups = DB::table('dormitory_group')->whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
        //$groups = implode(',', $groups);
        $headimg['path'] = public_path($request['headimg']);
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = $request['receptionUserId'];
        $teacher = Teacher::where('senselink_id',$receptionUserId)->first();
        if(!$teacher){
            return showMsg('拜访人不存在');
        }
        $headimg['imgtype'] = 'image/'.$imagetype;

        //这里只是编辑信息
        $addArr = [
            'campusid'        => $request->campusid,
            'username'        => $request['username'],
            'headimg'         => $request['headimg'],
            'sex'             => $request['sex'],
            'begin_time'      => $request['begin_time'],
            'end_time'        => $request['end_time'],
            'visit_place'     => $request['visit_place'],
            'idnum'           => $teacher->idnum  ,
            'receptionuser'   => $teacher->username,
            'receptionUserId' => $receptionUserId
        ];
        if ($request['mobile']) {
            $addArr['mobile'] = $request['mobile'];
        }
        if ($request['ID_number']) {
            $addArr['ID_number'] = $request['ID_number'];
        }
        if ($request['visit_note']) {
            $addArr['visit_note'] = $request['visit_note'];
        }
        $res = Visit::where('link_id', $perInfo[0]['link_id'])->update($addArr);
        Queue::push(new SyncNebula([$request['id']],3,env('SENSE_NEBULA_WHITE_GROUP') ?? 1,2));
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }


    /**
     * 删除访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function del(Request $request)
    {
        $perInfo = Visit::where('id', $request['id'])->first();
        if (!$perInfo) {
            return $this->response->error('删除失败',201);
        }
        if($perInfo->link_id){  //link有数据
            $result_link = $this->senselink->linkguest_del($perInfo->link_id);
            if ($result_link['code'] == 200 && isset($result_link['code'])) {
                $res = Visit::where('id', $request['id'])->delete();
            } else {
                if($result_link['code'] == 30001){ //访客不存在
                    $res = Visit::where('id', $request['id'])->delete();
                }else {
                    return $this->response->error('删除失败' . $result_link['message'], 201);
                }
            }
        }else{
            $res = Visit::where('id', $perInfo->id)->delete();
        }
        if($perInfo->img_id) {
            Queue::push(new SyncNebula(unserialize($perInfo->img_id), 3, env('SENSE_NEBULA_WHITE_GROUP') ?? 1, 3));
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 获取访客列表
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function lists(Request $request)
    {
        $res = Visit::where(function ($req) use ($request) {
            if($request->campusid)  $req->where('campusid',$request->campusid);
            if($request->buildid)  $req->whereRaw("FIND_IN_SET($request->buildid,visit_place)");
            if ($request['search'] && is_numeric($request['search'])) $req->where('ID_number', 'like', '%'.$request['search'].'%');
            if ($request['search'] && is_numeric($request['search']) == false) $req->where('username', 'like', '%'.$request['search'].'%');
            if ($request['begin_time']) $req->where('begin_time', '>=', $request['begin_time']);
            if ($request['end_time']) $req->where('end_time', '<=', $request['end_time']);
            if ($request->status) $req->where('status',$request->status);
        })->orderBy('id', 'desc')->paginate($request['pageSize'])->toArray();
        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 获取访客记录列表
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function logss(Request $request)
    {
        $res = DormitoryGuestAccessRecord::where(function ($req) use ($request) {
            if($request->campusname) $req->where('campusname',$request->campusname);
            if ($request['buildid'])    $req->where('buildid', $request['buildid']);
            if ($request['begin_time']) $req->where('pass_time', '>=', $request['begin_time']);
            if ($request['end_time'])   $req->where('pass_time', '<=', $request['end_time'].' 23:59:59');
        })->orderBy('id', 'desc')->paginate($request['pageSize'])->toArray();
        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 批量审核
     * @param ids 集合id
     * @param status 2通过3拒绝
     * @param refuse_note 拒绝原因
     */
    public function state(Request $request){
        if(!$request->ids){
            return showMsg('缺少参数');
        }
        $ids = is_array($request->ids) ? $request->ids: json_decode($request->ids,true);
        $state = $request->status ?? 2;
        $refuse_note = $request->refuse_note ?: '';
        $message = '';
        if($state==3){  //拒绝
            Visit::whereIn('id',$ids)->update(['status'=>3,'refuse_note'=>$refuse_note]);
            self::pushdata($ids,1);
        }else{
            //dispatch(new AllocateGuest($ids));
            $message = self::checkguest($ids);
        }
        if($message){
            return showMsg($message);
        }else{
            return showMsg('操作成功',200);
        }

    }

    public static function checkguest($ids){
        $message = '';
        $guests = Visit::whereIn('id',$ids)->get();
        if($guests->isEmpty()){
            return false;
        }
        $senselink = new \senselink();
        foreach($guests as $v){
            if($v->status==2){
                $message .= $v->id.'状态不可操作';
            }
            $visit_place = explode(',', $v->visit_place);
            $groups = DormitoryGroup::whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
            $groups = implode(',', $groups);
            $headimg['path'] = public_path($v->headimg);
            $imagetype = substr(strrchr($headimg['path'],'.'),1);
            if (strtolower($imagetype) == 'jpg') {
                $imagetype = 'jpeg';
            }
            $receptionUserId = $v->receptionUserId;
            $headimg['imgtype'] = 'image/'.$imagetype;
            $result_link = $senselink->linkguest_add($v->username, $receptionUserId, $headimg, $v->begin_time, $v->end_time, $groups);
            file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',添加访客：'.json_encode($result_link).PHP_EOL,FILE_APPEND);
            if ($result_link['code'] == 30002) {
                //如果link判断人员已存在，则先判断人员步骤如下：1 黑名单（暂时忽略） 2 学生 教职工 3 已存在的访客

                //2.判断学生or教师
                $stuInfo = Student::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
                if ($stuInfo == true) {
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',此人员为校内学生, 无法添加为访客'.PHP_EOL,FILE_APPEND);
                    $message .= ','.$v->id.'此人员为校内学生, 无法添加为访客';
                    continue;
                }
                $teacherInfo = Teacher::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
                if ($teacherInfo == true) {
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',此人员为校内教职工, 无法添加为访客'.PHP_EOL,FILE_APPEND);
                    $message .= ','.$v->id.'此人员为校内教职工, 无法添加为访客';
                    continue;
                }
                $perInfo = Visit::where('link_id', $result_link['data']['similar_user_id'])->first();
                file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',相似访客：'.json_encode($perInfo).PHP_EOL,FILE_APPEND);
                if ($perInfo) {
                    //作废旧的
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',访客已存在，作废'.PHP_EOL,FILE_APPEND);
                    Visit::whereId($perInfo->id)->update(['status' => 4]);
                }
                $result_links = $senselink->linkguest_del($result_link['data']['similar_user_id']);
                if ($result_links['code'] == 200) {
                    $result_link = $senselink->linkguest_add($v->username, $receptionUserId, $headimg, $v->begin_time, $v->end_time, $groups);
                }
            }
            if ($result_link['code'] == 200 && isset($result_link['code'])) {
                Visit::whereId($v->id)->update(['status'=>2,'link_id'=>$result_link['data']['id'],'confirm_time'=> date('Y-m-d H:i:s')]);
                self::pushdata($v->id,2);
                Queue::push(new SyncNebula([$v->id],3,env('SENSE_NEBULA_WHITE_GROUP') ?? 1,1));
                continue;
            } else {
                file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.','.$result_link['message'].PHP_EOL,FILE_APPEND);
                $message .= ',记录'.$v->id.$result_link['message'];
                continue;
            }
        }
        return $message;
    }

    /**
     * 推送模板消息给访客
     * @param ids 申请id合集
     */
    public static function pushdata($ids,$type){
        if($type==1){  //拒绝
            $list = Visit::whereIn('id',$ids)->get();
            foreach($list as $v){
                if(!$v->openid){
                    continue;
                }
                $post_data = array(
                    'touser' => $v->openid,  //用户openid
                    'template_id' => env('WECHATPUSH_REFUSE'), //在公众号下配置的模板id
                    'data' => array(
                        'first' => array('value' => "您好，您的预约被拒绝"),
                        'keyword1' => array('value' => $v->receptionuser),  //keyword需要与配置的模板消息对应
                        'keyword2' => array('value' => $v->begin_time.'至'.$v->end_time),
                        'keyword3' => array('value' => $v->visit_note),
                        'keyword4' => array('value' => $v->refuse_note),
                        'remark' => array('value' => '请与被访者确认后再次提交预约申请，感谢您的使用。', 'color' => '#FF0000'),
                    )
                );
                Push($post_data);
            }
        }else{  //单个推送
            $visit = Visit::whereId($ids)->first();
            if($visit && $visit->openid){
                $post_data = array(
                    'touser' => $visit->openid,  //用户openid
                    'template_id' => env('WECHAT_PUSH_PASS'), //在公众号下配置的模板id
                    'data' => array(
                        'first' => array('value' => "您好，您的预约已成功。"),
                        'keyword1' => array('value' => $visit->receptionuser),  //keyword需要与配置的模板消息对应
                        'keyword2' => array('value' => $visit->begin_time.'至'.$visit->end_time),
                        'keyword3' => array('value' => $visit->visit_note),
                        'remark' => array('value' => '请在预约时间内到访，过期无效，感谢您的使用。', 'color' => '#FF0000'),
                    )
                );
                Push($post_data);
            }
        }
    }

    /**
     * 访问管理导出
     * @param Request $request
     */
    public function export(Request $request){

        $res = Visit::where(function ($req) use ($request) {
            if($request->campusid)  $req->where('campusid',$request->campusid);
            if($request->buildid)  $req->whereRaw("FIND_IN_SET($request->buildid,visit_place)");
            if ($request['search'] && is_numeric($request['search'])) $req->where('ID_number', 'like', '%'.$request['search'].'%');
            if ($request['search'] && is_numeric($request['search']) == false) $req->where('username', 'like', '%'.$request['search'].'%');
            if ($request['begin_time']) $req->where('begin_time', '>=', $request['begin_time']);
            if ($request['end_time']) $req->where('end_time', '<=', $request['end_time']);
            if ($request->status) $req->where('status',$request->status);
        })->orderBy('id','desc')
            ->get()
            ->toArray();

        $header = [[
            'username'         =>  '访客姓名',
            'sex_name'         =>  '性别',
            'mobile'           =>  '手机号',
            'ID_number'        =>  '身份证号码',
            'visit_note'       =>  '来访目的',
            'begin_time'       =>  '通行有效时间-开始',
            'end_time'         =>  '通行有效时间-结束',
            'follow'           =>  '随行人数',
            'has_car_name'     =>  '是否开车',
            'carnum'           =>  '车牌号',
            'receptionuser'    =>  '受访人',
            'campus_name'      =>  '校区',
            'status_name'      =>  '状态',
        ]];

        $excel = new Export($res, $header,'访客管理');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }


    /**
     * 访问识别记录导出
     * @param Request $request
     */
    public function logExport(Request $request){

        $res = DormitoryGuestAccessRecord::where(function ($req) use ($request) {
            if($request['campusname']) $req->where('campusname',$request['campusname']);
            if ($request['buildid'])    $req->where('buildid', $request['buildid']);
            if ($request['begin_time']) $req->where('pass_time', '>=', $request['begin_time']);
            if ($request['end_time'])   $req->where('pass_time', '<=', $request['end_time']);
        })->orderBy('id','desc')
            ->get()
            ->toArray();

        $header = [[
            'pass_time'      =>  '识别时间',
            'pass_location'  =>  '识别地点',
            'pass_way'       =>  '识别通道',
            'truename'       =>  '姓名',
            'mobile'         =>  '手机号',
            'ID_number'      =>  '身份证号码',
            'sex'            =>  '性别',
            'note'           =>  '来访目的',
            'campusname'     =>  '校区',
            'visit'          =>  '来访地点',
            'touser'         =>  '受访人',
        ]];

        $excel = new Export($res, $header,'访问识别记录');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }
}
