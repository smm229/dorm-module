<?php

namespace Modules\Dorm\Jobs;

use senselink;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersBuilding;

class AllocateBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务可以尝试的最大次数。
     *
     * @var int
     */
    public $tries = 5;

    /**
     * 任务失败前允许的最大异常数
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * 任务可以执行的最大秒数 (超时时间)。
     *
     * @var int
     */
    public $timeout = 120;

    private $data,$buildid,$teacherids;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data,$buildid='',$teacherids='')
    {
        $this->data = $data;
        $this->buildid = $buildid;
        $this->teacherids = $teacherids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'开始执行--AllocateBuild--分配宿管楼宇队列任务,入参data:'.json_encode($this->data).PHP_EOL,FILE_APPEND);
        if(empty($this->data)){
            file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s') ."参数异常--Synclink--队列中止".PHP_EOL,FILE_APPEND);
            //$this->delete();
            return false;
        }
        try {
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'AllocateBuild--尝试失败次数过多'.PHP_EOL,FILE_APPEND);
                //$this->delete();
                return false;
            } else {
                //非管理员
                foreach($this->data as $v) {
                    if ($v['idnum'] != 'admin') {
                        $builds = DormitoryUsersBuilding::where('idnum', $v['idnum'])->pluck('buildid')->toArray();
                        if(RedisGet('builds-' . $v['idnum'])){
                            Redis::del(env('RedisPrefix').'builds-' . $v['idnum']);
                        }
                        RedisSet('builds-' . $v['idnum'], $builds, 7200);
                    }
                }
                //管理员
                $abuilds = DormitoryGroup::whereType(1)->pluck('id')->toArray();
                if(RedisGet('builds-admin')){
                    Redis::del(env('RedisPrefix').'builds-admin');
                }
                RedisSet('builds-admin', $abuilds, 7200);

                //添加宿管老师到组
                $senselink = new senselink();
                $group = DormitoryGroup::where('id', $this->buildid)->first();//楼宇的组
                $groupid = $group->groupid;
                $visitor_groupid = $group->visitor_groupid;//访客组
                //删除之前的老师关系
                if($this->teacherids){
                    file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--开始删除老师与用户组关系，数据：'.json_encode($this->teacherids).PHP_EOL,FILE_APPEND);
                    $linkids = Teacher::whereIn('idnum',$this->teacherids)->whereNotNull('senselink_id')->pluck('senselink_id')->toArray();//教师对应的linkid
                    $res_link = $senselink->person_delgroup($linkids, $groupid);
                    if (isset($res_link['code']) && $res_link['code'] == 200) {
                        file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--删除老师到用户组成功,idnums：'.json_encode($linkids).PHP_EOL,FILE_APPEND);
                    }else{
                        file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--删除老师到用户组失败,link数据：'.json_encode($res_link).PHP_EOL,FILE_APPEND);
                    }
                    $visitor_link = $senselink->person_delgroup($linkids, $visitor_groupid);
                    file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--删除老师到访客组返回link数据：'.json_encode($visitor_link).PHP_EOL,FILE_APPEND);
                }
                $newidnums = array_column($this->data, 'idnum');//取出idnum集合
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--开始绑定老师与用户组关系，数据：'.json_encode($newidnums).PHP_EOL,FILE_APPEND);
                $newlinkids = Teacher::whereIn('idnum',$newidnums)->whereNotNull('senselink_id')->pluck('senselink_id')->toArray();//教师对应的linkid
                $result_link = $senselink->linkperson_addgroup($newlinkids, $groupid);
                if (isset($result_link['code']) && $result_link['code'] == 200) {
                    file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--分配老师到用户组成功,idnums：'.json_encode($newidnums).PHP_EOL,FILE_APPEND);
                }else{
                    file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--分配老师到用户组失败,link数据：'.json_encode($result_link).PHP_EOL,FILE_APPEND);
                }
                $visitor_link = $senselink->linkperson_addgroup($newlinkids, $visitor_groupid);
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'分配宿管楼宇--分配老师到访客组返回link数据：'.json_encode($visitor_link).PHP_EOL,FILE_APPEND);
                //$this->delete();
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'队列--分配宿管楼宇--执行结束'.PHP_EOL,FILE_APPEND);
                return true;
            }
        }catch(\Exception $exception){
            //$this->delete();
            file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'AllocateBuild 队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage().PHP_EOL,FILE_APPEND);
            return false;
        }
    }
}
