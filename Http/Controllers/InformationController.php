<?php

namespace Modules\Dorm\Http\Controllers;
/*
 * 实时查询宿舍
 */
use App\Exports\Export;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryBlackAccessRecord;
use Modules\Dorm\Entities\DormitoryConfig;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryGuestAccessRecord;
use Modules\Dorm\Entities\DormitoryNoBackRecord;
use Modules\Dorm\Entities\DormitoryNoRecord;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryWarningRecord;
use Modules\Dorm\Http\Requests\DormitoryRoomValidate;
use Excel;

class InformationController extends Controller
{

    public function __construct()
    {

    }

    /*
     * 实时查寝
     * @param buildid int 楼宇id
     * @param floornum int 楼层
     */
    public function realtime(Request $request){
        $pagesize = $request->pageSize ?? 15;
        //只查询自己权限的宿舍
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        $idnum = auth()->user() ? auth()->user()->idnum : '';

        $list = DormitoryRoom::where(function ($q) use ($request,$uid,$idnum) {
                if ($request->buildid) $q->where('buildid', $request->buildid);
                if ($request->floornum) $q->where('floornum', $request->floornum);
                //按照楼栋筛选
                if ($uid!=1) $q->whereIn('buildid',function ($query) use ($idnum){
                    $query->select('buildid')->from('dormitory_users_building')->where('idnum',$idnum);
                });
            })
            ->with(['dormitory_beds'=>function ($q){
                $q->orderBy('id','asc');
            }])
            ->orderBy('id','asc')
            ->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

    /*
     * 综合数据
     */
    public function data(Request $request){
        //楼宇数量
        $list['total_buildings'] = DormitoryGroup::whereType(1)->count();
        //入住人数
        $list['total_user'] = DormitoryBeds::whereNotNull('idnum')->count();
        //空床位
        $list['total_beds'] = DormitoryBeds::whereNull('idnum')->count();
        $senselink = new \senselink();
        $links = $senselink->linkdevice_list('',1,1000);
        $online = $outline = 0;
        if($links['code']==200 && isset($links['data'])){
            $device = $links['data']['data'];
            array_walk($device,function($value,$key) use($device,&$online,&$outline) {
                if($value['device']['status']==1){ //在线
                    $online++;
                }else{
                    $outline++;
                }
            });
        }
        //设备在线数
        $list['online_device'] = $online;
        //设备离线数
        $list['outline_device'] = $outline;
        //昨日数据
        $list['build'] = $this->builds();
        return showMsg('获取成功',200,$list);
    }

    /*
     * 昨日数据
     */
    private function builds(){
        //楼宇列表
        $builds = DormitoryGroup::whereType(1)->get(['id','title'])->map(function ($build) {
            $yesterday = date('Y-m-d',strtotime('-1 day'));
            //入住率
            $build->probability = $build->total_beds ? round($build->total_person*100/$build->total_beds): 0;
            //晚归数量
            $build->later = DormitoryAccessRecord::whereType(1)
                ->where('buildid',$build->id)
                ->whereStatus(1)
                ->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])
                ->count();
            //未归
            $build->no_back = DormitoryNoBackRecord::where('buildid',$build->id)->where('date',$yesterday)->count();
            //多天无记录
            $build->no_record = DormitoryNoRecord::where('buildid',$build->id)->where('date',$yesterday)->count();
            //访客
            $build->guest = DormitoryGuestAccessRecord::where('buildid',$build->id)
                ->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])
                ->count();
            //黑名单
            $build->black = DormitoryBlackAccessRecord::where('buildid',$build->id)
                ->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])
                ->count();
            //非法记录
            $build->warning = DormitoryWarningRecord::where('buildid',$build->id)
                ->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])
                ->count();
            return $build;
        });

        return $builds;
    }
}
