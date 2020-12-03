<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Http\Requests\DormitoryBedsValidate;
use Modules\Dorm\Http\Requests\DormitoryRoomValidate;

class DormBedsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['AuthDel'])->only(['del','del_cate']);
    }

    /*
     * 床位列表
     * @param buildid int 宿舍楼id
     * @param floornum int 楼层
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 12;
        //当前宿管管理那栋楼
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        $idnum = auth()->user() ? auth()->user()->idnum : '';
        $list = DormitoryRoom::where(function ($q) use ($request,$uid,$idnum){
                if($uid !=1) $q->whereIn('buildid',function ($query) use ($idnum){
                    $query->from('dormitory_users_building')->where('idnum',$idnum)->pluck('buildid');
                });
                if($request->buildid) $q->where('buildid',$request->buildid);
                if($request->floornum) $q->where('floornum',$request->floornum);
                //房间号
                if($request->roomnum) $q->where('roomnum',$request->roomnum);
            })
            ->with(['dormitory_beds'=>function ($q){
                $q->select('id','bednum','roomid','idnum')->orderBy('id','asc');
            }])
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

    /*
     * 床位详情
     */
    public function detail(Request $request){
        if(!$request->roomid){ //宿舍id
            return showMsg('请选择宿舍');
        }
        $list = DormitoryBeds::where('roomid',$request->roomid)
            ->with('student')
            ->orderBy('bednum','asc') //床位号
            ->get();
        return showMsg('获取成功',200,$list);
    }

    /*
     * 调宿
     * @param bedsid int 床位id
     * @param idnum int 学员学号
     * @param type 类型 1分配宿舍 2调宿
     */
    public function change(DormitoryBedsValidate $request){
        $beds = DormitoryBeds::find($request->bedsid);
        $type = $request->type ?? 1; //1分配宿舍 2调宿
        if(!$beds){
            return showMsg('床位不存在');
        }
        if($beds->idnum){ //有人员住
            return showMsg('请选择其他床位');
        }
        try {
            DB::transaction(function () use ($beds,$request,$type){
                if($type==2){ //调宿
                    //删除原来的宿舍分配
                    DormitoryBeds::whereId($beds->id)->update(['idnum'=>'']);
                }
                //分配学员
                $beds->idnum = $request->idnum;
                $beds->save();
            });

            return showMsg('分配成功', 200);
        }catch(\Exception $e){
            return showMsg('分配失败');
        }
    }

    /*
     * 删除学员,退宿
     */
    public function del(Request $request){
        try {
            if (is_array($request->bedsid)) { //批量退宿
                DormitoryBeds::whereIn('id', $request->bedsid)->update(['idnum' => '']);
            } else {
                DormitoryBeds::whereId($request->bedsid)->update(['idnum' => '']);
            }
            return showMsg('删除成功', 200);
        }catch(\Exception $e) {
            return showMsg('删除失败');
        }
    }

    /*
     * 批量退宿
     * @param buildid int 宿舍楼
     * @param floornum int 楼层
     * @param roomid int 房间号
     * @param username string 姓名
     * @param idnum int 学号
     */
    public function users(Request $request){
        $pagesize = $request->pagesize ?? 12;
        //当前宿管查看自己的楼栋
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        $idnum = auth()->user() ? auth()->user()->idnum : '';
        $roomids = DormitoryRoom::where(function ($q) use ($request,$uid,$idnum){
                if($uid !=1) $q->whereIn('buildid',function ($query) use ($idnum){
                    $query->from('dormitory_users_building')->where('idnum',$idnum)->pluck('buildid');
                });
                if($request->buildid) $q->where('buildid',$request->buildid);
                if($request->floornum) $q->where('floornum',$request->floornum);
                //房间号
                if($request->roomnum) $q->where('roomnum',$request->roomnum);
            })
            ->pluck('id')
            ->toArray();

        $list = DormitoryBeds::whereIn('roomid',$roomids)
            ->whereNotNull('idnum')
            ->with('student')
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }
}
