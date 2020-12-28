<?php

namespace Modules\Dorm\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryGroup;
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
        $res = [];
        //取出楼宇下的设备
        if ($request['buildid']) {
            $deviceids = DB::table('dormitory_building_device')->where('groupid', $request['buildid'])->pluck('deviceid')->toArray();
           if ($deviceids) {
               $ids = implode(',', $deviceids);
           }
        }
        if (($request['buildid'] && $ids) || $ids == '') {
            $result = $this->senselink->linkdevice_list($ids, $request['page'], $request['pageSize'],$request['location'], $request['name']);
            if ($result['message'] != 'OK') {
                return $this->response->error('获取列表失败',201);
            }
            $res = $result['data'];
        }
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

        $result = $this->senselink->linkdevice_edit($request['id'], $request['name'], $request['location'], $request['direction'], $request['groupid']);
        //设备绑定员工组，要生成记录
        if ($request['groupid']) {
            $insert = [
                'deviceid' => $request['id'],
                'groupid'  => $request['groupid']
            ];
        }
        if (!$result['status_code'] != 200) {
            return $this->response->error('编辑数据失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);
    }

    public function delete(Request $request)
    {
        if (!$request['id']) {
            return $this->response->error('请求数据错误',201);
        }
        $request_url = $this->link_host.'/api/v3/device/delete?timestamp='.$this->timestamp.'&app_key='.$this->app_key.'&sign='.$this->sign.'&id='.$request['id'];
        $client = new Client();
        $response = $client->request('get', $request_url);
        $result = json_decode($response->getBody()->getContents(), true);
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $result]);
    }

    public function test(Request $request)
    {
        $senselink = new senselink();
        $groupid   = '8';
        $res = $senselink->linkgroup_del($groupid);
        dd($res);

    }
}
