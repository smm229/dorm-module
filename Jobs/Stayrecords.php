<?php

namespace Modules\Dorm\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Illuminate\Support\Facades\Queue;
use Log;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryStayrecords;

class Stayrecords implements ShouldQueue
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

    private $data,$type,$buildid;

    /*
    * 增加住、退宿记录
    * @param type 类型 1分配宿舍，2调宿 ,3退宿
    */
    public function __construct($data, $type, $buildid=0)
    {
        $this->data = $data;
        $this->type = $type;
        $this->buildid = $buildid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s').'开始执行--Stayrecords--队列任务,入参data:'.json_encode($this->data).PHP_EOL,FILE_APPEND);
        if(empty($this->data)){
            file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') ."参数异常--Synclink--队列中止".PHP_EOL,FILE_APPEND);
            //$this->delete();
            return false;
        }
        try {
            file_put_contents(storage_path('logs/stayrecords.log'),'attempts次数'.$this->attempts().PHP_EOL,FILE_APPEND);
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                file_put_contents(storage_path('logs/stayrecords.log'),'Stayrecords--尝试失败次数过多'.PHP_EOL,FILE_APPEND);
                //$this->delete();
                return false;
            } else {
                //执行住宿记录
                if($this->type==3){ //退宿
                    self::reverse($this->data);
                }else{
                    self::record($this->data,$this->type,$this->buildid);
                }
                //$this->delete();
                file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行结束'.PHP_EOL,FILE_APPEND);
                return true;
            }
        }catch(\Exception $exception){
            //$this->delete();
            file_put_contents(storage_path('logs/stayrecords.log'),'队列任务执行失败'.$exception->getFile().$exception->getLine().$exception->getMessage().PHP_EOL,FILE_APPEND);
            file_put_contents(storage_path('logs/stayrecords.log'),'数据内容：'.json_encode($this->data).PHP_EOL,FILE_APPEND);
            return false;
        }
    }

    /*
    * 登记记录
    * @param data 床位记录，对象/数组
    * @param type 类型 1分配宿舍 2调宿 3退宿
    * @param buildid 原来的宿舍id，调宿用
    */
    public static function record($data,$type,$buildid){
        file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行第二步，增加记录，入参：'.json_encode($data).',type:'.$type.PHP_EOL,FILE_APPEND);
        $user = Student::where('idnum', $data->idnum)->first();
        if (!$user) return;
        try {
            if ($type == 2) { //调宿舍
                $info = DormitoryStayrecords::where('idnum', $data->idnum)->orderBy('id', 'desc')->first();
                if ($info) DormitoryStayrecords::whereId($info->id)->update(['updated_at' => date('Y-m-d H:i:s')]);
                //解除关系
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--解除绑定关系，调宿用户id：' . $user->senselink_id . '，到宿舍楼' . $buildid . PHP_EOL, FILE_APPEND);
                Queue::push(new SyncLink($user->senselink_id, $buildid, 2));
            }
            $floornum = DormitoryRoom::whereId($data->roomid)->value('floornum');
            $arr = [
                'username' => $user->username,//姓名
                'idnum' => $data->idnum,//学号
                'sex' => $user->sex_name,//性别
                'gradeName' => $user->grade,//年级
                'campusname'=>$user->campusname,
                'collegeName' => $user->collegename,//学院
                'majorName' => $user->majorname,//专业
                'className' => $user->classname,//班级
                'buildName' => $data->build_name,//楼栋
                'floor' => $floornum,//楼层
                'roomnum' => $data->room_num,//房间号
                'bednum' => $data->bednum,//床位号
            ];
            DormitoryStayrecords::insert($arr);
            //绑定关系
            file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--重新分配组关系，调宿用户id：' . $user->senselink_id . '，到宿舍楼' . $data->buildid . PHP_EOL, FILE_APPEND);
            if ($user->senselink_id) {
                Queue::push(new SyncLink($user->senselink_id, $data->buildid, 1));
                return true;
            } else {
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--分配失败，无senselinkid，调宿用户idnum：' . $user->idnum . '，到宿舍楼' . $buildid . PHP_EOL, FILE_APPEND);
                return false;
            }
        }catch(\Exception $e){
            file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--分配失败，用户工号：' . $user->idnum . '，到宿舍楼' . $buildid .'，错误信息：'. $e->getMessage(). PHP_EOL, FILE_APPEND);
            return false;
        }

    }

    /*
     * 批量退宿记录
     * @param array data
     */
    public static function reverse($data){
        file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行退宿,数据：'.json_encode($data).PHP_EOL,FILE_APPEND);

        foreach($data as $v){
            try {
                $info = DormitoryStayrecords::where('idnum', $v['idnum'])->orderBy('id', 'desc')->first();
                if ($info) DormitoryStayrecords::whereId($info->id)->update(['updated_at' => date('Y-m-d H:i:s')]);
                //解除关系
                $senselink_id = Student::where('idnum', $v['idnum'])->value('senselink_id');
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--批量解除组关系，退宿用户id：' . $senselink_id . PHP_EOL, FILE_APPEND);
                if ($senselink_id) {
                    Queue::push(new SyncLink($senselink_id, $v['buildid'], 2));
                    return true;
                }
            }catch(\Exception $e){
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--批量退宿解除关系失败用户学号：'.$v['idnum']  . PHP_EOL, FILE_APPEND);
                return false;
            }
        }

    }

}
