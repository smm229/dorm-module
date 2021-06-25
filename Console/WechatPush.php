<?php

namespace Modules\Dorm\Console;

use App\Models\Classes;
use App\Models\Student;
use Illuminate\Console\Command;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryLeave;
use Modules\Dorm\Entities\DormitoryNoBackRecord;
use Modules\Dorm\Entities\DormitoryNoRecord;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/*
 * 推送公众号模板消息
 * Author by xiangyang
 * Email 648128278@qq.com
 * create by 2021-05-08
 */

class WechatPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wechat_push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送模板消息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = date('Y-m-d',strtotime('-1 day'));
        //查询每栋楼的数据
        $builds = DormitoryGroup::whereType(1)->get();
        if($builds->first()) {
            foreach($builds as $v) {
                //查询宿管人员信息
                $list = DormitoryUsersBuilding::leftjoin('personnel_oauth','personnel_oauth.idnum','=','dormitory_users_building.idnum')
                            ->select('personnel_oauth.openid')
                            ->where('dormitory_users_building.buildid',$v->id)
                            ->where('personnel_oauth.type',2)
                            ->where('personnel_oauth.channel','mp')
                            ->get();
                if($list->first()) {
                    foreach($list as $vv) {
                        //晚归人员
                        $later = DormitoryAccessRecord::whereType(1)
                            ->where('buildid',$v->id)
                            ->whereStatus(1)
                            ->whereBetween('pass_time',[$date,$date.' 23:59:59'])
                            ->count();
                        //未归
                        $noback = DormitoryNoBackRecord::whereType(1)
                            ->where('buildid',$v->id)
                            ->where('date',$date)
                            ->count();
                        //多日无记录
                        $norecord = DormitoryNoRecord::whereType(1)
                            ->where('buildid',$v->id)
                            ->where('end_date',$date)
                            ->count();
                        //请假人员
                        $studentids = DormitoryBeds::where('buildid',$v->id)
                            ->where('is_in','>',0)
                            ->pluck('idnum')
                            ->toArray();
                        $leave = DormitoryLeave::where('status','<',3)
                            ->whereIn('idnum',$studentids)
                            ->where('start_time','<=',$date.' 23:59:59')->where('end_time','>=',$date)
                            ->count();
                        if($later || $noback || $norecord || $leave){  //有数据
                            $post_data = array(
                                'touser' => $vv->openid,  //用户openid
                                'template_id' => env('WECHATPUSH_INFOMATION'), //在公众号下配置的模板id
                                'url' => env('WEB_URL')."/h5/#/pages/house-manage-page/report-form/index?date=$date&buildid=".$v->id, //点击模板消息会跳转的链接
                                'data' => array(
                                    'first' => array('value' => $v->title."宿舍楼昨日数据推送"),
                                    'keyword1' => array('value' => date('Y-m-d 00:00:00', strtotime('-1 day'))),  //keyword需要与配置的模板消息对应
                                    'keyword2' => array('value' => date('Y-m-d 23:59:59', strtotime('-1 day'))),
                                    'remark' => array('value' => '点击详情查看明细', 'color' => '#FF0000'),
                                )
                            );
                            Push($post_data);
                        }

                    }
                }
            }
        }

        //推送数据给班主任
        $list = Classes::leftjoin('personnel_oauth','personnel_oauth.idnum','=','personnel_classes.idnum')
                ->select('personnel_oauth.*')
                ->where('personnel_oauth.type',2)
                ->where('personnel_oauth.channel','mp')
                ->groupBy('personnel_classes.idnum')
                ->get();
        if($list->first()){
            foreach($list as $v){
                $class = Classes::where('idnum',$v->idnum)->first();
                $studentids = $class ? Student::where('classid',$class->id)->pluck('idnum')->toArray() : [];
                if($studentids) {
                    //晚归
                    $later = DormitoryAccessRecord::whereType(1)
                        ->whereIn('idnum', $studentids)
                        ->whereStatus(1)
                        ->whereBetween('pass_time', [$date, $date . ' 23:59:59'])
                        ->count();
                    //未归
                    $noback = DormitoryNoBackRecord::whereType(1)
                        ->whereIn('idnum', $studentids)
                        ->where('date', $date)
                        ->count();
                    //无记录
                    $norecord = DormitoryNoRecord::whereType(1)
                        ->whereIn('idnum', $studentids)
                        ->where('end_date', $date)
                        ->count();
                    //请假
                    $leave = DormitoryLeave::where('status', '<', 3)
                        ->whereIn('idnum', $studentids)
                        ->where('start_time', '<=', $date . ' 23:59:59')->where('end_time', '>=', $date)
                        ->count();
                    if($later || $noback || $norecord || $leave) {  //有数据
                        $post_data = array(
                            'touser' => $v->openid,  //用户openid
                            'template_id' => env('WECHATPUSH_INFOMATION'), //在公众号下配置的模板id
                            'url' => env('WEB_URL') . "/h5/#/pages/teacher-page/report-form/index?date=$date", //点击模板消息会跳转的链接
                            'data' => array(
                                'first' => array('value' => $v->title . "宿舍楼昨日数据推送"),
                                'keyword1' => array('value' => date('Y-m-d 00:00:00', strtotime('-1 day'))),  //keyword需要与配置的模板消息对应
                                'keyword2' => array('value' => date('Y-m-d 23:59:59', strtotime('-1 day'))),
                                'remark' => array('value' => '点击详情查看明细', 'color' => '#FF0000'),
                            )
                        );
                        Push($post_data);
                    }
                }
            }
        }
        //推送给学生处
        $student = Student::leftjoin('personnel_oauth','personnel_oauth.idnum','=','personnel_student.idnum')
            ->select('personnel_oauth.*')
            ->where('personnel_oauth.type',1)
            ->where('personnel_oauth.channel','mp')
            ->where('personnel_student.role',1) //学生会
            ->get();
        if($builds->first() && $student->first()) {
            foreach ($builds as $v) {
                foreach ($student as $vv) {
                    //晚归人员
                    $later = DormitoryAccessRecord::whereType(1)
                        ->where('buildid',$v->id)
                        ->whereStatus(1)
                        ->whereBetween('pass_time',[$date,$date.' 23:59:59'])
                        ->count();
                    //未归
                    $noback = DormitoryNoBackRecord::whereType(1)
                        ->where('buildid',$v->id)
                        ->where('date',$date)
                        ->count();
                    //多日无记录
                    $norecord = DormitoryNoRecord::whereType(1)
                        ->where('buildid',$v->id)
                        ->where('end_date',$date)
                        ->count();
                    //请假人员
                    $studentids = DormitoryBeds::where('buildid',$v->id)
                        ->where('is_in','>',0)
                        ->pluck('idnum')
                        ->toArray();
                    $leave = DormitoryLeave::where('status','<',3)
                        ->whereIn('idnum',$studentids)
                        ->where('start_time','<=',$date.' 23:59:59')->where('end_time','>=',$date)
                        ->count();
                    if($later || $noback || $norecord || $leave) {  //有数据
                        $post_data = array(
                            'touser' => $vv->openid,  //用户openid
                            'template_id' => env('WECHATPUSH_INFOMATION'), //在公众号下配置的模板id
                            'url' => env('WEB_URL') . "/h5/#/pages/student-page/report-form/index?date=$date&buildid=" . $v->id, //点击模板消息会跳转的链接
                            'data' => array(
                                'first' => array('value' => $v->title . "宿舍楼昨日数据推送"),
                                'keyword1' => array('value' => date('Y-m-d 00:00:00', strtotime('-1 day'))),  //keyword需要与配置的模板消息对应
                                'keyword2' => array('value' => date('Y-m-d 23:59:59', strtotime('-1 day'))),
                                'remark' => array('value' => '点击详情查看明细', 'color' => '#FF0000'),
                            )
                        );
                        Push($post_data);
                    }
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
