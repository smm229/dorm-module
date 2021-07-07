<?php

namespace Modules\Dorm\Http\Controllers;

use App\Extend\SenseNebula;
use App\Models\Campus;
use App\Models\Student;
use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBuildingDevice;
use Modules\Dorm\Entities\DormitoryElectric;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryNebula;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use Modules\Dorm\Http\Requests\DormitoryBuildingDeviceValidate;
use senselink;
use Illuminate\Support\Facades\Validator;
class DeviceController extends Controller
{
    use Helpers;
    public function __construct()
    {
        $this->senselink = new senselink();
        $this->direction = [
            '1' => '进',
            '2' => '出',
            '0' => '默认'
        ];
        $this->modename = [
            'IMAGE' => '图片流',
            'VIDEO' => '视频流',
            'CAPTURE'=>'纯抓拍',
             ''=>''
        ];
        $this->type = [
            [
                'key'=> 3,
                'name' => 'SenseKeeper 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 4,
                'name' => 'SensePass 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 5,
                'name' => 'SensePass Pro 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 6,
                'name' => 'SensePass X 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 13,
                'name' => 'SensePass C 人脸识别一体机',
                'type' => 1
            ],
            [
                'key'=> 15,
                'name' => 'SensePass Lite 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 14,
                'name' => 'SenseAike 人脸识别一体机',
                'type' => 1
            ],
            [
                'key'=> 2,
                'name' => 'SenseID 身份验证一体机',
                'type' => 1
            ],
            [
                'key'=> 7,
                'name' => 'SenseGate-B 人脸识别机',
                'type' => 1
            ],
            [
                'key'=> 8,
                'name' => 'SenseGate-H 人脸识别一体机',
                'type' => 1
            ],
            [
                'key'=> 10,
                'name' => 'SenseNebula-M 智能边缘节点',
                'type' => 1
            ],
            [
                'key'=> 20,
                'name' => 'SenseThunder-E 火神测温识别',
                'type' => 1
            ],
            [
                'key'=> 19,
                'name' => 'SenseThunder-E 火神 Mini 测温识别机',
                'type' => 1
            ],
            [
                'key'=> 16,
                'name' => 'SenseThunder-W 风神测温识别机',
                'type' => 1
            ],
            [
                'key'=> 100,
                'name' => '摄像头',
                'type' => 2
            ],
            [
                'key'=> 101,
                'name' => '门禁控制板',
                'type' => 3
            ]
        ];
    }

    /**
     * 获取设备列表
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function lists(Request $request)
    {
        $type =  $this->getType($request['type']);
        if($type ==1){  //link
            $ids = '';
            //取出楼宇下的设备
            if (!empty($request['buildid'])) {
                $groupId = DormitoryGroup::where('id', $request['buildid'])->value('groupid');
                $deviceids = DB::table('dormitory_building_device')->where('type','1')->where('groupid', $groupId)->pluck('deviceid')->toArray() ;
                if (!$deviceids) { //无设备
                    return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => []]);
                }
                $ids = implode(',', $deviceids);
                $result = $this->senselink->linkdevice_list($ids, '1', '2000',$request['location'], $request['name'],$request['type']);
            }elseif (!empty($request['campusid'])){
                $groupId = DormitoryGroup::where('campusid', $request['campusid'])->pluck('groupid')->toArray();
                $deviceids = DB::table('dormitory_building_device')->where('type','1')->whereIn('groupid', $groupId)->pluck('deviceid')->toArray() ;
                if (!$deviceids) { //无设备
                    return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => []]);
                }
                $ids = implode(',', $deviceids);
                $result = $this->senselink->linkdevice_list($ids,'1', '2000',$request['location'], $request['name'],$request['type']);
            }else{ //全部
                $result = $this->senselink->linkdevice_list($ids, '1', '2000',$request['location'], $request['name'],$request['type']);
            }
            if ($result['message'] != 'OK') {
                return $this->response->error('获取列表失败',201);
            }
            $data =$result['data']['data'];
            if(($request['status'])){
                $arr = [];
                foreach ($data as $k=>$v){
                    if($v['device']['status'] == $request['status']){
                        array_push($arr,$data[$k]);
                    }
                }
                $data = $arr;
            }
            $list = [];
            foreach ($data as $k=>$v){

                $list[$k]['id'] =  $v['device']['id'];
                $list[$k]['type'] = 1;
                $list[$k]['type_name'] = $v['device_type']['name'];
                $list[$k]['type_id'] = 1;
                $list[$k]['devicename'] =  $v['device']['name'];
                $list[$k]['position'] =  $v['device']['location'];
                $list[$k]['senselink_sn'] =  $v['device']['sn'];
                $list[$k]['status'] =  $v['device']['status'];
            }
            $res = $list;
        }elseif ($type ==2){  //摄像头
            $res =  DormitoryBuildingDevice::where(function ($q) use ($request){
                if($request['campusid']) $q->where('campusid', $request['campusid']);
                if($request['buildid']){
                    $groupId = DormitoryGroup::where('id', $request['buildid'])->value('groupid');
                    $q->where('groupid',$groupId);
                }
                if($request['status']) $q->where('status', $request['status']);
            })->where('type','2')->orderBy('id','desc')->get();

        }elseif ($type ==3){  //门禁控制板

            $res =  DormitoryBuildingDevice::where(function ($q) use ($request){
                if($request['campusid']) $q->where('campusid', $request['campusid']);
                if($request['buildid']){
                    $groupId = DormitoryGroup::where('id', $request['buildid'])->value('groupid');
                    $q->where('groupid',$groupId);
                }
                if($request['status']) $q->where('status', $request['status']);
            })->where('type','3')->orderBy('id','desc')->get();
        }else{
            return showMsg('错误参数');
        }

        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => ['data'=>$res,'type'=>$type]]);

    }

    /**
     * 获取设备详情
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function info(Request $request)
    {
        if (!$request['id'] || !$request['type']) {
            return $this->response->error('参数错误',201);
        }
//        $type =  $this->getType($request['type']);

        if($request['type'] == 1){
            $result = $this->senselink->linkdevice_list($request['id']);
            if ($result['message'] != 'OK') {
                return $this->response->error('获取失败',201);
            }
            $res = $result['data']['data'];
            //获取设备所在楼栋
            $groupid = DormitoryBuildingDevice::where('deviceid', $request['id'])->where('grouptype', 1)->first()->toArray();
            $resArr = [];
            foreach ($res as $v) {
                $resArr['deviceid']           = $v['device']['id'];
                $resArr['devicetype']         = $v['device_type']['name'];
                $resArr['deviceLDID']         = $v['device']['sn'];
                $resArr['devicename']         = $v['device']['name'];
                $resArr['direction']          = $this->direction[$v['device']['direction']];
                $resArr['location']           = $v['device']['location'];
                $resArr['groupid']            = $groupid['groupid'];
                $resArr['buildname']          = DormitoryGroup::where('groupid', $groupid['groupid'])->value('title');
                $resArr['campus_name']        = Campus::where('id', $groupid['campusid'])->value('name');
                $resArr['campusid']           = $groupid['campusid'];
                $resArr['ip']                 = $v['device']['ip'];
                $resArr['status']             = $v['device']['status'];
                $resArr['create_at']          = $v['device']['create_at'];
                $resArr['last_offline_time']  = date('Y-m-d H:i:s',$v['device']['last_offline_time']);
                $resArr['update_at']          = $v['device']['update_at'];
                $resArr['software_version']   = $v['device']['software_version'];
            }
            $resArrs[] = $resArr;
            $type = 1;
        }elseif ($request['type'] ==2){
            $resArrs = DormitoryBuildingDevice::where('id',$request['id'])->first();

            $resArrs['modename'] = $this->modename[$resArrs['mode']];
            $resArrs['type'] = '摄像头';
            $resArrs['campus_name'] = Campus::where('id',$resArrs['campusid'])->value('name');
            $resArrs['buildname'] = DormitoryGroup::where('groupid', $resArrs['groupid'])->value('title');
            $type =2;
        }else{

            $resArrs = DormitoryBuildingDevice::where('id',$request['id'])->first();
            $resArrs['type'] = '门禁控制板';
            $resArrs['campus_name'] = Campus::where('id',$resArrs['campusid'])->value('name');
            $resArrs['buildname'] = DormitoryGroup::where('groupid', $resArrs['groupid'])->value('title');
            $type =3;
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => ['resArrs'=>$resArrs,'type'=>$type]]);
    }

    /**
     * 编辑设备
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function edit(DormitoryBuildingDeviceValidate $request)
    {

        if($request['type'] == 1){

            //因为设备默认要有员工，访客，黑名单的组
            $groupArr = DormitoryGroup::where('groupid', $request['groupid'])->get(['groupid', 'visitor_groupid'])->toArray();
            if ($groupArr && $groupArr[0]['groupid'] && $groupArr[0]['visitor_groupid']) {
                DB::beginTransaction();
                //删除设备的绑定关系
                DormitoryBuildingDevice::where('deviceid', $request['id'])->delete();
                $insert[] = [
                    'deviceid'     => $request['id'],
                    'groupid'      => $groupArr[0]['groupid'],
                    'senselink_sn' => $request['senselink_sn'],
                    'grouptype'    => 1,
                    'devicename'   => $request['devicename'],
                    'position'     => $request['position'],
                    'campusid'     => $request['campusid'],
                    'direction'    => $request['direction'],
                    'type'         =>1,
                ];
                $insert[] = [
                    'deviceid'     => $request['id'],
                    'groupid'      => $groupArr[0]['visitor_groupid'],
                    'senselink_sn' => $request['senselink_sn'],
                    'grouptype'    => 2,
                    'devicename'   => $request['devicename'],
                    'position'     => $request['position'],
                    'campusid'     => $request['campusid'],
                    'direction'    => $request['direction'],
                    'type'         =>1,
                ];
                DormitoryBuildingDevice::insert($insert);
                $result = $this->senselink->linkdevice_edit($request['id'], $request['devicename'], $request['position'], $request['direction'], $groupArr[0]['groupid'], $groupArr[0]['visitor_groupid']);
                if ($result['code'] == 200 && $result['message'] == 'OK') {
                    DB::commit();
                    return $this->response->array(['status_code' => 200, 'message'=> '成功']);
                }else{
                    DB::rollBack();
                    return $this->response->error('分配失败，请联系管理员',201);
                }
            }
        }elseif ($request['type'] == 2){
          $BuildingDevice =   DormitoryBuildingDevice::where('id',$request['id'])->first();
          $nebula_ip = DormitoryNebula::where('senselink_sn',$BuildingDevice['senselink_sn'])->value('ip');
          if($nebula_ip) {
              DB::beginTransaction();
              $data =  $request->only('devicename','position','campusid','groupid','direction');
              DormitoryBuildingDevice::where('id',$request['id'])->update($data);
              $sensenebula = new SenseNebula($nebula_ip);
              $cameraList = $sensenebula->EditCamera(['msg_id'=>'515','channel'=>$BuildingDevice['deviceid'],'position'=>$data['position'],'camera_name'=>$data['devicename']]);
              if($cameraList['code'] == 0){
                  DB::commit();
                  return $this->response->array(['status_code' => 200, 'message'=> '成功']);
              }else{
                  DB::rollBack();
                  return $this->response->error('分配失败，请联系管理员',201);
              }
          }
        }else{
            $data = $request->only('devicename','position','campusid','groupid','direction');
            if(  DormitoryBuildingDevice::where('id',$request['id'])->update($data)){
                return $this->response->array(['status_code' => 200, 'message'=> '成功']);
            }
        }
        return $this->response->error('分配失败，请联系管理员',201);
    }

    /**
     * 删除设备
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(Request $request)
    {
        if (!$request['id']) {
            return $this->response->error('参数错误',201);
        }
        //清除设备关系表
        DB::beginTransaction();
        $delRes = DormitoryBuildingDevice::where('deviceid', $request['id'])->delete();
        $result = $this->senselink->linkdevice_del($request['id']);
        if ($result['code'] == 200 && $result['message'] != 'OK') {
            DB::commit();
        } else {
            DB::rollBack();
            return $this->response->error('删除设备失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $delRes]);
    }

    /**
     * 获取设备下的人员列表
     * @param Request $request
     */
    public function getPersonByDevice(Request $request){
        if (!$request['id']) {
            return $this->response->error('参数错误',201);
        }
        try{
            $groupidArr = DormitoryBuildingDevice::where('deviceid', $request['id'])->pluck('groupid')->toArray();
            $perArrs = [];
            $perArr = DormitoryUsersGroup::whereIn('groupid', $groupidArr)->paginate($request['pageSize'])->toArray();
            if ($perArr['data']) {
                $senselinkIds = [];
                foreach ($perArr['data'] as $v) {
                    $senselinkIds[] = $v['senselink_id'];
                }
                //获取教师的列表
                $teacherArr = Teacher::whereIn('senselink_id', $senselinkIds)->get()->toArray();
                $studentArr = Student::whereIn('senselink_id', $senselinkIds)->get()->toArray();
                $perArrs = array_merge($teacherArr, $studentArr);
            }
            $perArr['data'] = $perArrs;
        } catch (\Exception $exception) {
            return $this->response->error('获取人员失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $perArr]);
    }

    /**
     * 设备告警列表
     */
    public function alarm(Request $request){
        $page = $request->page ?? 1;
        $size = $request->pageSize ?? 10;
        $result = $this->senselink->linkdevice_alarm_list($page, $size);
        if($result['code']!=200){
            return showMsg('请刷新重试');
        }
        return showMsg('获取成功', 200, $result['data']);
    }

    /**
     * 解除设备告警
     */
    public function relieve(Request $request){
        if(!$request->traceId){
            return showMsg('请选择设备');
        }
        $result = $this->senselink->linkdevice_disarm($request->traceId);
        if($result['code']!=200){
            return showMsg('操作失败');
        }
        return showMsg('操作成功', 200);

    }

    /**
     * 电控记录
     * @param deviceName 设备名称
     * @param campusname 校区名称
     * @param college_name 院系名称
     * @param major_name 专业名称
     * @param class_name 班级名称
     * @param build_name 宿舍楼
     * @param roomnum 宿舍号
     * @param floor 楼层
     * @param type 电控类型
     * @pagesize 页码
     */
    public function electric(Request $request){
        $pagesize = $request->pageSize ?? 10;
        $list = DormitoryElectric::where(function ($q) use ($request){
            if($request->deviceName) $q->where('deviceName',$request->deviceName);
            if($request->campusname) $q->where('campusname',$request->campusname);
            if($request->build_name) $q->where('build_name',$request->build_name);
            if($request->floor) $q->where('floor',$request->floor);
            if($request->roomnum) $q->where('roomnum',$request->roomnum);
            if($request->type) $q->where('type',$request->type);
            if($request->college_name) $q->where('college_name',$request->college_name);
            if($request->major_name) $q->where('major_name',$request->major_name);
            if($request->class_name) $q->where('class_name',$request->class_name);
        })
            ->orderBy('id','desc')
            ->paginate($pagesize);
        return showMsg('成功',200,$list);
    }


    /**
     * 获取星云M信息(网关列表)
     */
    public function getNebula(){

        $list = DormitoryNebula::get()->toArray();
        return showMsg('成功',200,$list);
    }

    /**
     *获取星云列表(摄像头)
     * @param Request $request
     */
    public function getCameraList(Request $request){

        if(!$request['ip']){

            return showMsg('请选择网关');
        }
        $sensenebula = new SenseNebula($request['ip']);

        $cameraList = $sensenebula->getCameraList();
        if($cameraList['code'] == 0){
            return showMsg('成功',200,$cameraList['data']);
        }
        return showMsg('获取失败');
    }

    /**
     * 获取设备列表
     */
    public function typeList(){

        $list = $this->type;
        return showMsg('成功',200,$list);

    }

    public function getType($key = 'SPS'){

        $list = $this->type;
        $type = '';
        foreach ($list as $k=>$v){
            if($v['key'] == $key){
                $type = $v['type'];
            }
        }

        return $type;
    }

    /**
     * 添加设备
     * @param Request $request
     */
    public function add(Request $request){

        if(!$request['type']){
            return showMsg('请选择类型');
        }
        $input = $request->input();
        $validator =   $this->validate($input,$request['type']);
        if ($validator->fails()) {
            return showMsg('参数错误');
        }
        $host = explode('.',$request->host);
        if(count($host)!=4){
            return showMsg('ip非法');
        }
        if($request['type'] == 2){
            $Nebula = DormitoryNebula::where('ip',$request['ip'])->first();
            if (!$Nebula){
                return showMsg('无效网关');
            }

            $data = $request->only('type','host','devicename','direction','campusid','groupid','position','mode','protocol');
            $data['senselink_sn'] = $Nebula['senselink_sn'];
            $data['deviceid'] = $request['channel'];
            $data['groupid'] = DormitoryGroup::where('id',$data['groupid'])->value('groupid');
            try{
                DB::transaction(function () use ($data){

                    DormitoryBuildingDevice::insert($data);
                });
                return showMsg('添加成功',200);
            }catch(\Exception $e){
                return showMsg('添加失败');
            }
        }else{
            $data = $request->only('type','devicename','campusid','groupid','host','deviceid','position');
            $data['groupid'] = DormitoryGroup::where('id',$data['groupid'])->value('groupid');
            try{
                DB::transaction(function () use ($data){

                    DormitoryBuildingDevice::insert($data);
                });
                return showMsg('添加成功',200);
            }catch(\Exception $e){
                return showMsg('添加失败');
            }
        }

    }

    private function validate($input,$type)
    {
        if($type==2){

            $rules = [
                'type' => 'required|int',  //类型
                'ip' => 'required',        //网关
                'channel' => 'required',  //设备
                'host' => 'required',    //ip
                'devicename' => 'required',  //设备名称
                'direction' => 'required',  //设备方向
                'campusid' => 'required',   //校区
                'groupid' => 'required',   //楼
                'position' => 'required',  //设备位置
            ];
        }else{

            $rules = [
                'type' => 'required|int', //类型
                'devicename' => 'required', //设备名称
                'campusid' => 'required', //校区
                'groupid' => 'required',  //楼
                'host' => 'required',  //ip
                'deviceid' => 'required', // 设备编号
                'position' => 'required',  //设备位置
            ];
        }

        return Validator::make($input, $rules);

    }

    /**
     * 脚本同步星云设备在线状态
     */
    public function shStatusEdit(){

        $BuildingDevice = DormitoryBuildingDevice::where('type',2)->groupBy('senselink_sn')->get()->toArray();
        foreach ($BuildingDevice as $k=>$v){

            $nebula_ip = DormitoryNebula::where('senselink_sn',$v['senselink_sn'])->value('ip');
            $sensenebula = new SenseNebula($nebula_ip);

            $list = $sensenebula->getCameraList();
            if($list['code'] == 0){
                foreach ($list['data']['camera'] as $key=>$val){

                    DormitoryBuildingDevice::where('senselink_sn',$v['senselink_sn'])->where('deviceid',$val['channel'])->update(['status'=>$val['status']]);
                }
            }
        }
    }
}
