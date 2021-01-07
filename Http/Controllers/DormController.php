<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBuildingDevice;
use Modules\Dorm\Entities\DormitoryCategory;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryUsers;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Modules\Dorm\Http\Requests\DormitoryBuildingsValidate;
use Excel;
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
        return Excel::download($excel, time().'.xlsx');

    }

    /*
     * 宿舍楼列表
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 12;
        //只查询自己权限的楼宇
        $buildids = RedisGet('builds-'.auth()->user()->id);

        $list = DormitoryGroup::select('id', 'title', 'floor')
            ->whereType(1)
            ->whereIn('id',$buildids)
            ->with(['dormitory_users' => function ($q) {
                $q->select('dormitory_users.id', 'dormitory_users.username', 'dormitory_users.idnum');
            }])
            ->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

    /*
     * 添加楼宇
     * @param title 名称
     * @param buildtype 楼宇类型id
     * @param floor 楼层
     * @param teachers 宿管老师idnum集合
     */
    public function add(DormitoryBuildingsValidate $request){
        try{
            if(DormitoryGroup::whereTitle($request->title)->first()){
                throw new \Exception('请更换名称');
            }
            DB::beginTransaction();
            $buildid = DormitoryGroup::insertGetId([
                'title'         =>  $request->title,
                'type'          =>  1,
                'buildtype'     =>  $request->buildtype,
                'floor'         =>  $request->floor,
            ]);
            if ($request->teachers) {
                $users = explode(',',$request->teachers);
                $build = ['buildid' => $buildid];
                //宿管关联表
                array_walk($users, function (&$value, $key, $build) {
                    $value = array_merge(['idnum'=>$value], $build);
                }, $build);
                DormitoryUsersBuilding::insert($users);
            }
            //如果添加成功，添加link
            if ($buildid) {
                $res = $this->senselink->linkgroup_add($request->title, 1);
                if (isset($res['data']) && isset($res['data']['id'])) {
                    $upArr = [
                        'groupid' => $res['data']['id'],
                    ];
                    $upRes = DormitoryGroup::where('id', $buildid)->update($upArr);
                    DB::commit();
                    return showMsg('操作成功',200);
                } else {
                    DB::rollBack();
                    return showMsg('添加失败');
                }
            }
        }catch(\Exception $e) {
            return showMsg('添加失败');
        }
    }

    /*
    * 编辑楼宇
    * @param title 名称
    * @param buildtype 楼宇类型id
    * @param floor 楼层
    * @param ename 英文名称
    * @param icon 图标
    * @param teachers 宿管老师idnum集合
    */
    public function edit(DormitoryBuildingsValidate $request){
         try{
            if(!$info = DormitoryGroup::whereId($request->id)->first()){
                throw new \Exception('信息不存在');
            }
            if(DormitoryGroup::whereTitle($request->title)->where('id','<>',$request->id)->first()){
                throw new \Exception('请更换名称');
            }
            //查看楼层
            if($request->floor<$info->floor){
                throw new \Exception('楼层不能低于原楼层');
            }
            DB::transaction(function () use ($request,$info){
                $info->title         =  $request->title;
                $info->buildtype     =  $request->buildtype;
                $info->floor         =  $request->floor;
                $info->save();
                if ($request->teachers) {
                    $build = ['buildid'=>$info->id];
                    DormitoryUsersBuilding::where($build)->delete();
                    $users = explode(',',$request->teachers);
                    //宿管关联表
                    array_walk($users, function (&$value, $key, $build) {
                        $value = array_merge(['idnum'=>$value], $build);
                    }, $build);
                    DormitoryUsersBuilding::insert($users);
                }
            });
            //更新link的信息
            $res = $this->senselink->linkgroup_edit($request->title, $info['groupid']);
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }
    }

    /*
     * 删除楼宇
     */
    public function del(Request $request){
        if(!$info = DormitoryGroup::whereId($request->id)->first()){
            return showMsg('信息不存在');
        }
        if(DormitoryUsersBuilding::where('buildid',$request->id)->count()>0 || DormitoryRoom::where('buildid',$request->id)->count()>0){
            return showMsg('无法删除');
        }
        if (!$info['groupid']) {
            DormitoryGroup::whereId($request->id)->delete();
            return showMsg('删除成功',200);
        } else {
            DB::beginTransaction();
            DormitoryGroup::whereId($request->id)->delete();
            //删除link上的组
            $res = $this->senselink->linkgroup_del($info['groupid']);
            if (isset($res['code']) && $res['code'] == 200) {
                DB::commit();
                return showMsg('删除成功',200);
            } else {
                DB::rollBack();
                return showMsg('删除失败');
            }
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

    /**
     * 楼宇分配设备
     */
    public function bindDevice(Request $request) {
        if (!$request['id']) {
            return showMsg('参数不全');
        }
        if(!$info = DormitoryGroup::whereId($request->id)->first()){
            return showMsg('信息不存在');
        }
        //删除所有关系表
        try {
            $delRes = DormitoryBuildingDevice::where('groupid', $request['id'])->delete();
            $res = $this->senselink->linkgroup_edit('', $info['groupid'], $request['devices']);
            if (isset($res['data']['devices']) && $res['data']['devices']) {
                //生成本地数据库关联表
                foreach ($res['data']['devices'] as $k => $v) {
                    $deviceIdArr[$k]['groupid']  = $request->id;
                    $deviceIdArr[$k]['deviceid'] = $v['id'];
                }
                $res = DormitoryBuildingDevice::insert($deviceIdArr);
                if (!$res) {
                    return showMsg('添加失败');
                }
            }
            return showMsg('添加成功', 200);
        } catch (\Exception $exception) {
            return showMsg('添加失败');
        }
    }
}
