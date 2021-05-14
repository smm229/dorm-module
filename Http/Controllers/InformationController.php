<?php

namespace Modules\Dorm\Http\Controllers;
/*
 * 实时查询宿舍
 */
use App\Exports\Export;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryBlackAccessRecord;
use Modules\Dorm\Entities\DormitoryConfig;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryGuestAccessRecord;
use Modules\Dorm\Entities\DormitoryLeave;
use Modules\Dorm\Entities\DormitoryNoBackRecord;
use Modules\Dorm\Entities\DormitoryNoRecord;
use Modules\Dorm\Entities\DormitoryRepair;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryStrangeAccessRecord;
use Modules\Dorm\Entities\DormitoryWarningRecord;
use Modules\Dorm\Http\Requests\DormitoryRoomValidate;
use Excel;

class InformationController extends Controller
{

    public function __construct()
    {

    }

    /**
     * 首页
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        //楼宇数
        $data['total_build'] = DormitoryGroup::where('type',1)->count();
        //房间数
        $data['total_room'] = DormitoryRoom::count();
        //床位数
        $data['total_bed'] = DormitoryBeds::count();
        //未入住
        $data['no_stay'] = DormitoryBeds::where('is_in',0)->count();
        //入住
        $data['stay'] = $data['total_bed']-$data['no_stay'];
        //入住率
        $data['stay_percrent'] = $data['total_bed'] ? sprintf("%.1f",($data['total_bed']-$data['no_stay'])*100/$data['total_bed']) : '0';
        //设备在线率
        $senselink = new \senselink();
        $res = $senselink->linkdevice_list('',1,2000);
        $online = $offline = $total_device = 0;
        if($res['code']==200 && !empty($res['data'])){
            $total_device = $res['data']['total']; //设备总数
            $list = $res['data']['data'];
            if(!empty($list)){
                foreach($list as $v){
                    if(!empty($v['device']) && $v['device']['status']==1){ //在线
                        $online +=1;
                    }else{
                        $offline +=1;
                    }
                }
            }
        }
        $data['total_device'] = $total_device;//总设备
        $data['online_device'] = $online;//在线
        $data['offline_device'] = $offline;//离线
        $data['online_percent'] = $total_device==0 ? '0' : sprintf("%.1f",$online*100/$total_device);
        //归寝率
        $data['student'] = Student::count();
        $data['student_back'] = DormitoryBeds::where('is_in',1)->count(); //在宿舍
        $data['student_back_percent'] = $data['student'] ? sprintf("%.1f",$data['student_back']*100/$data['student']) : '0';
        $data['student_noback'] = $data['student']-$data['student_back']; //未归
        //设备报修
        //处理中 已处理 未处理
        $data['total_repair'] = DormitoryRepair::count(); //报修总数
        $data['no_repair'] = DormitoryRepair::where('status',1)->count();//未处理
        $data['repair'] = DormitoryRepair::where('status',4)->count();//已处理
        $data['repairing'] = $data['total_repair']-$data['no_repair']-$data['repair'];//处理中
        //昨日出勤
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        $data['noback'] = DormitoryNoBackRecord::where('date',$yesterday)->count();//昨日未归记录
        $data['later_back'] = DormitoryAccessRecord::where('status',1)->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//晚归记录数
        $data['leave'] = DormitoryLeave::where('start_time','>=',$yesterday)->where('end_time','<=',$yesterday.' 23:59:59')->where('status',2)->count();//请假
        //识别统计,当天
        $data['illegal_record'] = DormitoryWarningRecord::whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//非法通行
        $data['teacher_recotd'] = DormitoryAccessRecord::where('type',2)->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//教师通行记录
        $data['student_record'] = DormitoryAccessRecord::where('type',1)->whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//学生通行记录
        $data['guest_record'] = DormitoryGuestAccessRecord::whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//访客通行记录
        $data['strange_record'] = DormitoryStrangeAccessRecord::whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//陌生人通行记录
        $data['black_record'] = DormitoryBlackAccessRecord::whereBetween('pass_time',[$yesterday,$yesterday.' 23:59:59'])->count();//黑名单通行记录
        //通行记录，中间区域
        $data['pics'] = DormitoryAccessRecord::leftjoin('personnel_student as s','s.idnum','=','dormitory_access_record.idnum')
            ->where('dormitory_access_record.type',1)
            ->orderBy('dormitory_access_record.id','desc')
            ->groupBy('s.idnum')
            ->take(20)
            ->get(['s.username','s.headimg','dormitory_access_record.type']);
        //人脸识别记录,右侧
        $record_data = RedisGet('record_data');
        $data['record'] = !empty($record_data)  ? $record_data: [];
        //进出时间分布,当天
        $time_data = RedisGet('times_data');
        $data['times'] = !empty($time_data) ? $time_data: [];
        return showMsg('成功',200,$data);
    }

    /**
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

    /**
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

    /**
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
            $build->no_record = DormitoryNoRecord::where('buildid',$build->id)->where('end_date',$yesterday)->count();
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
