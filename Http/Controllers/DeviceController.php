<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBuildingDevice;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use senselink;

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
    }

    /**
     * 获取设备列表
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function lists(Request $request)
    {
        $ids = '';
        //取出楼宇下的设备
        if (!empty($request['buildid'])) {
            $deviceids = DB::table('dormitory_building_device')->where('groupid', $request['buildid'])->pluck('deviceid')->toArray() ;
            if (!$deviceids) { //无设备
                return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => []]);
            }
            $ids = implode(',', $deviceids);
            $result = $this->senselink->linkdevice_list($ids, $request['page'], $request['pageSize'],$request['location'], $request['name']);
        }else{ //全部
            $result = $this->senselink->linkdevice_list($ids, $request['page'], $request['pageSize'],$request['location'], $request['name']);
        }
        if ($result['message'] != 'OK') {
            return $this->response->error('获取列表失败',201);
        }
        $res = $result['data'];
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 获取设备详情
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function info(Request $request)
    {
        if (!$request['id']) {
            return $this->response->error('参数错误',201);
        }
        $result = $this->senselink->linkdevice_list($request['id']);
        if ($result['message'] != 'OK') {
            return $this->response->error('获取失败',201);
        }
        $res = $result['data']['data'];
        $buildname = '';
        //获取设备所在楼栋
        $groupid = DB::table('dormitory_building_device')->where('deviceid', $request['id'])->value('groupid');
        if ($groupid) {
            $buildname = DormitoryGroup::where('id', $groupid)->value('title');
        }
        $resArr = [];
        foreach ($res as $v) {
            $resArr['deviceid']           = $v['device']['id'];
            $resArr['devicetype']         = $v['device_type']['name'];
            $resArr['deviceLDID']         = $v['device']['sn'];
            $resArr['devicename']         = $v['device']['name'];
            $resArr['direction']          = $this->direction[$v['device']['direction']];
            $resArr['location']           = $v['device']['location'];
            $resArr['buildname']          = $buildname;
            $resArr['ip']                 = $v['device']['ip'];
            $resArr['status']             = $v['device']['status'];
            $resArr['create_at']          = $v['device']['create_at'];
            $resArr['last_offline_time']  = date('Y-m-d H:i:s',$v['device']['last_offline_time']);
            $resArr['update_at']          = $v['device']['update_at'];
            $resArr['software_version']   = $v['device']['software_version'];
        }
        $resArrs[] = $resArr;
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $resArrs]);
    }

    /**
     * 编辑设备
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function edit(Request $request)
    {
        if (!$request['id']) {
            return $this->response->error('参数错误',201);
        }
        //因为设备默认要有员工，访客，黑名单的组，所以要加上123
        $groupid = [$request['groupid'], 1, 2, 3];
        $result = $this->senselink->linkdevice_edit($request['id'], $request['name'], $request['location'], $request['direction'], $groupid);
        if ($result['code'] != 200 || false == $result['code']) {
            return $this->response->error('编辑数据失败',201);
        }
        //设备绑定员工组，要生成记录
        if ($request['groupid']) {
            try {
                DB::beginTransaction();
                //删除设备的绑定关系
                $delRes = DormitoryBuildingDevice::where('deviceid', $request['id'])->delete();
                $insert = [
                    'deviceid' => $request['id'],
                    'groupid'  => $request['groupid'],
                ];
                $insertRes = DormitoryBuildingDevice::insertGetId($insert);
                if ($insertRes) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    return $this->response->error('编辑数据失败',201);
                }
            } catch (\Exception $exception) {
                return $this->response->error('编辑数据失败',201);
            }

        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);

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
        if ($result['code'] == 200) {
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

    /*
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

    /*
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
}
