<?php

namespace Modules\Dorm\Http\Controllers;

use App\Exports\Export;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Http\Requests\DormitoryRoomListValidate;
use Modules\Dorm\Http\Requests\DormitoryRoomValidate;
use Excel;

class DormRoomController extends Controller
{

    public function __construct()
    {
        $this->middleware('AuthDel')->only(['del','del_cate']);
    }

    /*
     * 导出
     */
    public function export(Request $request){
        $header = [[
            'building_name'     =>  '宿舍楼',
            'floornum'          =>  '宿舍楼层',
            'roomnum'           =>  '宿舍房间号',
            'bedsnum'           =>  '床位数',
            'buildtype_name'   =>  '床铺类型'
        ]];
        $idnum = auth()->user()->idnum;
        $buildids = RedisGet('builds-'.$idnum);
        $data = DormitoryRoom::whereIn('buildid',$buildids)->get()->toArray();
        $excel = new Export($data, $header,'宿舍信息');
        $file = 'file/'.time().'.xlsx';
        if(\Maatwebsite\Excel\Facades\Excel::store($excel, $file,'public')){
            return showMsg('成功',200,['url'=>'/uploads/'.$file]);
        }
        return showMsg('下载失败');
    }

    /*
     * 宿舍列表
     * @param buildid int 楼宇id
     * @param floornum int 楼层
     */
    public function lists(Request $request){
        $pagesize = $request->pageSize ?? 12;
        //只查询自己权限的宿舍
        $idnum = auth()->user()->idnum;
        $buildids = RedisGet('builds-'.$idnum);
        $list = DormitoryRoom::whereIn('buildid',$buildids)
            ->where(function ($q) use ($request) {
            if ($request->buildid) $q->where('buildid', $request->buildid);
            if ($request->floornum) $q->where('floornum', $request->floornum);
        })->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

   /**
    * 添加宿舍
    * @param roomnum 房间号
    * @param buildtype 楼宇类型id
    * @param floornum 楼层
    * @param bedsnum 床位数
    * @param buildid 楼宇id
    * @param campusid 校区id
    */
    public function add(DormitoryRoomValidate $request){
        try{
            $build = DormitoryGroup::find($request->buildid);
            if(!$build || $build->floor<$request->floornum){
                throw new \Exception('楼宇不存在或楼层错误');
            }
            if(DormitoryRoom::where([
                'campusid' => $request->campusid,
                'roomnum'=>$request->roomnum,
                'floornum'=>$request->floornum,
                'buildid'=>$request->buildid
            ])->first()){
                throw new \Exception('房间号已存在');
            }
            DB::transaction(function () use ($request){
                //房间
                $roomid = DormitoryRoom::insertGetId([
                    'campusid'        =>  $request->campusid,
                    'roomnum'         =>  $request->roomnum,
                    'buildtype'       =>  $request->buildtype,
                    'floornum'        =>  $request->floornum,
                    'bedsnum'         =>  $request->bedsnum,
                    'buildid'         =>  $request->buildid
                ]);
                //床位
                for($i=1;$i<=$request->bedsnum;$i++) {
                    $arr = [
                        'campusid' =>  $request->campusid,
                        'buildid' => $request->buildid,
                        'roomid' => $roomid,
                        'room_num'=>$request->roomnum,
                        'bednum' => $i
                    ];
                    DormitoryBeds::insert($arr);
                }
            });
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }
    }

    /*
     * 批量添加宿舍
    * @param start 起始房间号
    * @param end 起始房间号
    * @param buildtype 楼宇类型id
    * @param floornum 楼层
    * @param bedsnum 床位数
    * @param buildid 楼宇id
    */
    public function addList(DormitoryRoomListValidate $request){
        if($request->start>$request->end){
            return showMsg('起始房间号不能大于截止房间号');
        }
        try{
            $build = DormitoryGroup::find($request->buildid);
            if(!$build || $build->floor<$request->floornum){
                throw new \Exception('楼宇不存在或楼层错误');
            }
            DB::transaction(function () use ($request){
                for($j=$request->start;$j<=$request->end;$j++) {
                    if (DormitoryRoom::where([
                        'campusid' =>  $request->campusid,
                        'roomnum' => $j,
                        'floornum' => $request->floornum,
                        'buildid' => $request->buildid
                    ])->first()
                    ) {
                        continue;
                    }
                    //房间
                    $roomid = DormitoryRoom::insertGetId([
                        'campusid'  =>  $request->campusid,
                        'roomnum' => $j,
                        'buildtype' => $request->buildtype,
                        'floornum' => $request->floornum,
                        'bedsnum' => $request->bedsnum,
                        'buildid' => $request->buildid
                    ]);
                    //床位
                    for ($i = 1; $i <= $request->bedsnum; $i++) {
                        $arr = [
                            'campusid' =>  $request->campusid,
                            'buildid' => $request->buildid,
                            'roomid' => $roomid,
                            'room_num'=>$j,
                            'bednum' => $i
                        ];
                        DormitoryBeds::insert($arr);
                    }
                }
            });
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败'.$e->getMessage());
        }
    }

    /*
    * 编辑宿舍
    * @param roomnum 房间号
    * @param buildtype 楼宇类型id
    * @param floornum 楼层
    * @param bedsnum 床位数
    * @param buildid 楼宇id
    * @param id 宿舍id
    */
    public function edit(DormitoryRoomValidate $request){
        try{
            $build = DormitoryGroup::find($request->buildid);
            if(!$build || $build->floor<$request->floornum){
                throw new \Exception('楼宇不存在或楼层错误');
            }
            if(!$info = DormitoryRoom::whereId($request->id)->first()){
                throw new \Exception('信息不存在');
            }
            if(DormitoryRoom::where([
                    'campusid' =>  $request->campusid,
                    'roomnum'=>$request->roomnum,
                    'floornum'=>$request->floornum,
                    'buildid'=>$request->buildid
                ])->where('id','<>',$request->id)
                ->first()){
                throw new \Exception('房间号已存在');
            }

            DB::transaction(function () use ($request,$info){
                $info->campusid       =  $request->campusid;
                $info->roomnum        =  $request->roomnum;
                $info->buildtype      =  $request->buildtype;
                $info->floornum       =  $request->floornum;
                $info->bedsnum        =  $request->bedsnum;
                $info->buildid        =  $request->buildid;
                $info->save();
                //清除床位信息
                DormitoryBeds::whereBuildid($info->buildid)->where('roomid',$info->id)->delete();
                //新增床位
                for($i=1;$i<=$request->bedsnum;$i++) {
                    $arr = [
                        'campusid'        =>  $request->campusid,
                        'buildid' => $request->buildid,
                        'roomid' => $info->id,
                        'room_num'=>$info->roomnum,
                        'bednum' => $i
                    ];
                    DormitoryBeds::insert($arr);
                }
            });
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('操作失败'.$e->getMessage());
        }
    }

    /*
     * 删除宿舍
     */
    public function del(Request $request){
        if(!DormitoryRoom::whereId($request->id)->first()){
            return showMsg('信息不存在');
        }
        //是否有人居住
        if(DormitoryBeds::where('roomid',$request->id)->whereNotNull('idnum')->first()){
            return showMsg('宿舍有人居住，禁止删除');
        }
        try{
            DB::transaction(function () use ($request){
                //删除宿舍
                DormitoryRoom::whereId($request->id)->delete();
                //删除床位
                DormitoryBeds::where('roomid',$request->id)->delete();
            });
            return showMsg('删除成功',200);
        }catch(\Exception $e){
            return showMsg('操作失败');
        }

    }

}
