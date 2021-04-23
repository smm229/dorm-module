<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Dorm\Entities\DormitoryBuildingDevice;
use Modules\Dorm\Entities\DormitoryCategory;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use Modules\Dorm\Http\Requests\DormitoryBuildingsValidate;
use Modules\Dorm\Jobs\AllocateBuild;
use Excel;
use phpDocumentor\Reflection\Types\False_;
use senselink;

class DormController extends Controller
{

    public function __construct()
    {
        $this->senselink = new senselink();
        $this->middleware('AuthDel')->only(['del','del_cate']);
    }

    /*
     * 调出excel
     * 暂时导出全部
     */
    public function export(Request $request)
    {
        //设置表头
        $header = [
            [
                "title"=>'名称',
                "floor"=>'楼层数',
                "buildtype_name"=>'楼宇类型',
                "total_room"=>'房间总数',
                "total_beds"=>'床位总数',
                "total_person"=>'入住人数',
                "total_empty_beds"=>'空床位数',
                "teachers"=>'宿管老师'
            ]
        ];
        $data = DormitoryGroup::whereType(1)->get()->toArray();
        if($data){
            foreach($data as &$v){
                $teacher = DormitoryUsers::whereIn('idnum',function ($q) use ($v){
                        $q->select('idnum')->from('dormitory_users_building')->where('buildid',$v['id'])->get();
                    })
                    ->pluck('dormitory_users.username')
                    ->toArray();
                $v['teachers'] = implode(',',$teacher);
            }
        }
        $excel = new Export($data, $header,'宿舍楼信息');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>$file]);
        }
        return showMsg('下载失败');
    }

    /*
     * 宿舍楼or权限组列表
     */
    public function lists(Request $request){
        $pagesize = $request->pageSize ?? 12;
        $type     = $request->type?$request->type:DormitoryGroup::DORMTYPE;
        if ($type  == DormitoryGroup::DORMTYPE) {
            //宿管只能查看自己的宿舍楼信息
            $idnum = auth()->user()->idnum;
            $buildids = RedisGet('builds-'.$idnum);
            $list = DormitoryGroup::whereType($type)
                ->whereIn('id',$buildids)
                ->with(['dormitory_users' => function ($q) {
                    $q->select('dormitory_users.id', 'dormitory_users.username', 'dormitory_users.idnum');
                }])->orderBy('id', 'desc')->paginate($pagesize);
        }
        //超级管理员查看所有组列表
        if ($type == DormitoryGroup::GROUPTYPE && auth()->user()->id == 1) {
            $list = DormitoryGroup::where(function ($req) use ($request){
                if ($request['search'])  $req->where('title', 'like', '%'.$request['search'].'%');
            })->orderBy('id', 'desc')->paginate($pagesize);
        }
        return showMsg('获取成功',200, $list);
    }

    /*
     * 添加权限组or宿舍楼
     * @param title       名称
     * @param buildtype   楼宇类型id
     * @param floor       楼层
     * @param teachers    宿管老师idnum集合
     * @param $device_ids 设备id合集
     */
    public function add(Request $request){
        if (!$request->title) {
            return showMsg('缺少必要参数');
        }
        if(DormitoryGroup::whereTitle($request->title)->first()){
            return showMsg('名称重复，请更换');
        }
        $type = $request->type?$request->type:DormitoryGroup::DORMTYPE;
        if ($type == DormitoryGroup::DORMTYPE) {
            if (!$request->buildtype || !$request->floor) {
                return showMsg('缺少必要参数');
            }
        }
        DB::beginTransaction();
        try{
            $users = [];
            if ($type == DormitoryGroup::DORMTYPE) {
                $buildid = DormitoryGroup::insertGetId([
                    'title'         =>  $request->title,
                    'type'          =>  DormitoryGroup::DORMTYPE,
                    'buildtype'     =>  $request->buildtype,
                    'floor'         =>  $request->floor,
                ]);
                if ($request->teachers) {
                    $users = explode(',', $request->teachers);
                    $build = ['buildid' => $buildid];
                    //宿管关联表
                    array_walk($users, function (&$value, $key, $build) {
                        $value = array_merge(['idnum'=>$value], $build);
                    }, $build);
                    DormitoryUsersBuilding::insert($users);
                }
            }
            if ($type ==  DormitoryGroup::GROUPTYPE) {
                $buildid = DormitoryGroup::insertGetId([
                    'title'   =>  $request->title,
                    'type'    =>  DormitoryGroup::GROUPTYPE,
                ]);
            }
            //权限组分配设备
            $devices_Ids = [];
            if ($request->deviceIds) {
                $devicesC = $devicesB = json_decode($request->deviceIds, true);
                $devices_Ids = implode(',', array_column($devicesB, 'id'));
            }
            //如果添加成功，添加link到员工组，访客组，黑名单组
            if ($buildid) {
                $blackId = env("LIKEGROUP_BLACKID") ?? 3;
                //添加员工组
                $res = $this->senselink->linkgroup_add($request->title, 1);
                //添加访客组
                $visitorRes = $this->senselink->linkgroup_add($request->title, 2);
                if (isset($res['data']) && isset($res['data']['id']) && isset($visitorRes['data']['id'])  && isset($visitorRes['data'])) {
                    $upArr = [
                        'groupid'           => $res['data']['id'],
                        'visitor_groupid'   => $visitorRes['data']['id'],
                    ];
                    DormitoryGroup::where('id', $buildid)->update($upArr);
                    //link内分别给三个类型组分配设备
                    $resD          = $this->senselink->linkgroup_edit(false, $res['data']['id'], $devices_Ids);
                    $visitorResD   = $this->senselink->linkgroup_edit(false, $visitorRes['data']['id'], $devices_Ids);
                    //查询所有设备加入默认黑名单
                    $blackDevices = $this->senselink->linkdevice_list('',1,10000);
                    if ($blackDevices['code'] == 200 && $blackDevices['message'] == 'OK' && isset($blackDevices['data']['data'])) {
                        $blackIDs = [];
                        foreach ($blackDevices['data']['data'] as $k => $v) {
                                $blackIDs[] = $v['device']['id'];
                        }
                        if (!empty($blackIDs)) {
                            $devices_Idss = implode(',', $blackIDs);
                            $blacklistResD = $this->senselink->linkgroup_edit(false, $blackId, $devices_Idss);
                        }
                    }
                    if (isset($resD['data']) && isset($resD['data']['id']) && isset($visitorResD['data']['id'])  && isset($visitorResD['data']) && isset($blacklistResD['data']['id']) && isset($blacklistResD['data'])) {
                        if ($request->deviceIds) {
                            $upArrD = ['groupid' => $res['data']['id'], 'grouptype' => 1];
                            array_walk($devicesB, function (&$value, $key, $upArrD) {
                                $value = array_merge(['deviceid'=>$value['id'], 'senselink_sn' => $value['senselink_sn'], 'name' => $value['name']], $upArrD);
                            }, $upArrD);
                            $upArrDs = ['groupid' => $visitorRes['data']['id'], 'grouptype' => 2];
                            array_walk($devicesC, function (&$value, $key, $upArrDs) {
                                $value = array_merge(['deviceid'=>$value['id'], 'senselink_sn' => $value['senselink_sn'], 'name' => $value['name']], $upArrDs);
                            }, $upArrDs);
                            $devices = array_merge_recursive($devicesB, $devicesC);
                            DormitoryBuildingDevice::insert($devices);
                        }
                    }
                    DB::commit();
                    //队列修改管理员所属楼宇
                    if($users) {
                        Queue::push(new AllocateBuild($users, $buildid));
                    }
                    return showMsg('操作成功',200);
                } else {
                    DB::rollBack();
                    return showMsg('添加失败');
                }
            }
        }catch(\Exception $e) {
            DB::rollBack();
            return showMsg($e->getMessage());
        }
    }

    /*
    * 编辑楼宇 or 通行权限组
    * @param title 名称
    * @param buildtype 楼宇类型id
    * @param floor 楼层
    * @param ename 英文名称
    * @param icon 图标
    * @param teachers 宿管老师idnum集合
    */
    public function edit(Request $request){
        try{
            if(!$info = DormitoryGroup::whereId($request->id)->first()){
                throw new \Exception('信息不存在');
            }
            if(DormitoryGroup::whereTitle($request->title)->where('id', '<>', $request->id)->first()){
                throw new \Exception('请更换名称');
            }
            DB::beginTransaction();
            $type = $request->type ? $request->type:DormitoryGroup::DORMTYPE;
            if ($type == DormitoryGroup::DORMTYPE) {
                if (!$request->buildtype || !$request->floor) {
                    throw new \Exception('缺少必要参数');
                }
                //查看楼层
                if($request->floor < $info->floor){
                    throw new \Exception('楼层不能低于原楼层');
                }
                $info->buildtype  =  $request->buildtype;
                $info->floor      =  $request->floor;
            }
            $info->title  =  $request->title;
            $info->save();
            $users = [];
            $build = ['buildid' => $info->id];
            if ($request->teachers) {
                $teacherids = DormitoryUsersBuilding::where($build)->pluck('idnum')->toArray();
                DormitoryUsersBuilding::where($build)->delete();
                $users = explode(',', $request->teachers);
                //宿管关联表
                array_walk($users, function (&$value, $key, $build) {
                    $value = array_merge(['idnum' => $value], $build);
                }, $build);
                DormitoryUsersBuilding::insert($users);
            }
            //编辑管辖设备，可以为空数组
            $blackId = env("LIKEGROUP_BLACKID") ?? 3;
            $devices_Ids = [];
            if ($request->deviceIds) {
                $devices_Ids = $request->deviceIds;
                DormitoryBuildingDevice::whereIn('groupid', [$info['groupid'], $info['visitor_groupid']])->delete();
                if ($request->deviceIds != 'delete') {
                    $devicesC = $devicesB = json_decode($request->deviceIds, true);
                    $devices_Ids = implode(',', array_column($devicesB, 'id'));
                    $upArrD = ['groupid' => $info['groupid'], 'grouptype' => 1];
                    array_walk($devicesB, function (&$value, $key, $upArrD) {
                        $value = array_merge(['deviceid'=>$value['id'], 'senselink_sn' => $value['senselink_sn'], 'name' => $value['name']], $upArrD);
                    }, $upArrD);
                    $upArrDs = ['groupid' => $info['visitor_groupid'], 'grouptype' => 2];
                    array_walk($devicesC, function (&$value, $key, $upArrDs) {
                        $value = array_merge(['deviceid'=>$value['id'], 'senselink_sn' => $value['senselink_sn'], 'name' => $value['name']], $upArrDs);
                    }, $upArrDs);
                    $devices = array_merge_recursive($devicesB, $devicesC);
                    DormitoryBuildingDevice::insert($devices);
                }
            }
            //更新link的组的信息
            $res           = $this->senselink->linkgroup_edit($request->title, $info['groupid'], $devices_Ids);
            Log::error('正常组编辑',$res);
            $visitorRes    = $this->senselink->linkgroup_edit($request->title, $info['visitor_groupid'], $devices_Ids);
            Log::error('访客组编辑',$visitorRes);
            //查询所有设备加入默认黑名单
            $blackDevices = $this->senselink->linkdevice_list('',1,10000);
            if ($blackDevices['code'] == 200 && $blackDevices['message'] == 'OK' && isset($blackDevices['data']['data'])) {
                $blackIDs = [];
                foreach ($blackDevices['data']['data'] as $k => $v) {
                    $blackIDs[] = $v['device']['id'];
                }
                if (!empty($blackIDs)) {
                    $devices_Idss = implode(',', $blackIDs);
                    $blacklistRes  = $this->senselink->linkgroup_edit($request->title, $blackId, $devices_Idss);
                    Log::error('黑名单组编辑',$blacklistRes);
                }
            }
            if (isset($res['data']) && isset($res['data']['id']) && isset($visitorRes['data']['id'])  && isset($visitorRes['data'])) {
                 DB::commit();
                 //队列修改管理员所属楼宇
                if($users) {
                    Queue::push(new AllocateBuild($users,$info->id, $teacherids));
                }
                 return showMsg('修改成功',200);
            } else {
                 DB::rollBack();
                 return showMsg('修改失败');
            }
        }catch(\Exception $e){
            return showMsg($e->getMessage());
        }
    }

    /*
     * 删除楼宇or通行权限组
     */
    public function del(Request $request){
        if(!$info = DormitoryGroup::whereId($request->id)->first()){
            return showMsg('信息不存在');
        }
        $type = $request->type ? $request->type:DormitoryGroup::DORMTYPE;
        if ($type == DormitoryGroup::DORMTYPE) {
            if(DormitoryRoom::where('buildid',$request->id)->count()>0){
                return showMsg('请先删除相关宿舍');
            }
        }
        try{
            DB::beginTransaction();
            DormitoryGroup::whereId($request->id)->delete();
            if ($type == DormitoryGroup::DORMTYPE) {
                DormitoryUsersBuilding::where('buildid', $request->id)->delete();
            }
            if ($type == DormitoryGroup::DORMTYPE) {
                DormitoryUsersGroup::whereIn('groupid', [$info['groupid'], $info['visitor_groupid']])->delete();
            }
            //用户组
            DormitoryBuildingDevice::where('groupid', $info['groupid'])->delete();
            //访客组
            DormitoryBuildingDevice::where('groupid', $info['visitor_groupid'])->delete();
            //删除link上的组
            $res = $this->senselink->linkgroup_del($info['groupid']);
            file_put_contents(storage_path('logs/del_group.log'),$info->id.'删除用户组'.json_encode($res).PHP_EOL,FILE_APPEND);
            $visitor = $this->senselink->linkgroup_del($info['visitor_groupid']);
            file_put_contents(storage_path('logs/del_group.log'),$info->id.'删除访客组'.json_encode($visitor).PHP_EOL,FILE_APPEND);
            if (isset($res['code']) && $res['code'] == 200) {
                DB::commit();
                return showMsg('删除成功',200);
            } else {
                DB::rollBack();
                return showMsg('删除失败');
            }
        }catch(\Exception $e){
            return showMsg($e->getMessage());
        }
    }

    /*
     * 添加楼宇类型
     * @param name string 名称
     * @param sort int 排序
     * @param describ string 描述
     */
    public function add_cate(Request $request){
        if(!$request->name){
            return showMsg('请填写名称');
        }
        $ckey = $request->ckey ?? 'dormitory';
        if(DormitoryCategory::whereName($request->name)->where( 'ckey' ,$ckey)->first()){
            return showMsg('名称已存在');
        }
        try {
            DormitoryCategory::insert([
                'name' => $request->name,
                'ckey' => $ckey,
                'sort' => $request->sort ?? 0,
                'describ' => $request->describ ?? ''
            ]);
            return showMsg('添加成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }

    }

    /*
     * 编辑楼宇类型
     * @param id int id
     * @param name string 名称
     * @param sort int 排序
     * @param describ string 描述
     */
    public function edit_cate(Request $request){
        if(!$request->name) return showMsg('请填写名称');

        if(!$info = DormitoryCategory::find($request->id)){
            return showMsg('信息不存在');
        }
        if($r=DormitoryCategory::whereName($request->name)->where( 'ckey' ,$info->ckey)->first()){
            if($r->id !=$request->id)  return showMsg('名称已存在');
        }
        try {
            $info->name = $request->name;
            $info->sort = $request->sort ?? 0;
            $info->describ = $request->describ ?? '';
            $info->save();
            return showMsg('编辑成功',200);
        }catch(\Exception $e){
            return showMsg('编辑失败');
        }
    }

    /*
     * 删除楼宇类型
     */
    public function del_cate(Request $request){
        $info = DormitoryCategory::find($request->id);
        if(!$info){
            return showMsg('信息不存在');
        }
        if($info->ckey=='dormitory') { //楼宇
            if (DormitoryGroup::where('buildtype', $request->id)->count() > 0) {
                return showMsg('无法删除');
            }
        }else{
            if(DormitoryRoom::where('buildtype',$request->id)->count()>0){
                return showMsg('无法删除');
            }
        }
        if(DormitoryCategory::where('ckey',$info->ckey)->whereId($request->id)->delete()) {
            return showMsg('删除成功', 200);
        }
        return showMsg('删除失败');
    }

    /*
     * 类型列表
     */
    public function cate_list(Request $request){
        $ckey = $request->ckey ?? 'dormitory';
        $list = DormitoryCategory::where('ckey',$ckey)
            ->orderBy('sort','asc')
            ->get(['id','name']);
        return showMsg('获取成功',200,$list);
    }


}
