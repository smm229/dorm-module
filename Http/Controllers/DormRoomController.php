<?php

namespace Modules\Dorm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Http\Requests\DormitoryRoomValidate;

class DormRoomController extends Controller
{

    public function __construct()
    {
        $this->middleware('AuthDel')->only(['del','del_cate']);
    }

    /*
     * 宿舍列表
     * @param buildid int 楼宇id
     * @param floornum int 楼层
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 12;
        //只查询自己权限的宿舍
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        $idnum = auth()->user() ? auth()->user()->idnum : '';

        $list = DormitoryRoom::where(function ($q) use ($request,$uid,$idnum) {
            if ($request->buildid) $q->where('buildid', $request->buildid);
            if ($request->floornum) $q->where('floornum', $request->floornum);
            //按照楼栋筛选
            if ($uid!=1) $q->whereIn('buildid',function ($query) use ($idnum){
                $query->from('dormitory_users_building')->where('idnum',$idnum)->pluck('buildid');
            });
        })->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

    /*
     * 添加宿舍
    * @param roomnum 房间号
    * @param buildtype 楼宇类型id
    * @param floornum 楼层
    * @param bedsnum 床位数
    * @param buildid 楼宇id
     */
    public function add(DormitoryRoomValidate $request){
        try{
            if(DormitoryRoom::where([
                'roomnum'=>$request->roomnum,
                'floornum'=>$request->floornum,
                'buildid'=>$request->buildid
            ])->first()){
                throw new \Exception('房间号已存在');
            }
            DB::transaction(function () use ($request){
                //房间
                $roomid = DormitoryRoom::insertGetId([
                    'roomnum'         =>  $request->roomnum,
                    'buildtype'       =>  $request->buildtype,
                    'floornum'        =>  $request->floornum,
                    'bedsnum'         =>  $request->bedsnum,
                    'buildid'         =>  $request->buildid
                ]);
                //床位
                for($i=1;$i<=$request->bedsnum;$i++) {
                    $arr = [
                        'buildid' => $request->buildid,
                        'roomid' => $roomid,
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
            if($info = DormitoryRoom::whereId($request->id)->first()){
                throw new \Exception('信息不存在');
            }
            if(DormitoryRoom::where([
                    'roomnum'=>$request->roomnum,
                    'floornum'=>$request->floornum,
                    'buildid'=>$request->buildid
                ])->where('id','<>',$request->id)
                ->first()){
                throw new \Exception('房间号已存在');
            }

            DB::transaction(function () use ($request,$info){
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
                        'buildid' => $request->buildid,
                        'roomid' => $info->id,
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
     * 删除宿舍
     */
    public function del(Request $request){
        if(!DormitoryRoom::whereId($request->id)->first()){
            return showMsg('信息不存在');
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
