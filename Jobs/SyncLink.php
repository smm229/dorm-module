<?php

namespace Modules\Dorm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use senselink;
class SyncLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId,$buildid,$type;

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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$buildid,$type)
    {
        $this->userId = $userId;//link用户id
        $this->buildid = $buildid; //楼宇id
        $this->type = $type; //1绑定2解除
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::error(date('Y-m-d H:i:s') .'开始执行--Synclink--解绑link队列任务');
        try {
            $senselink = new senselink();
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                Log::error('Synclink--尝试失败次数过多');
                $this->delete();
            } else {
                //查询楼宇绑定的link组
                $groupid = DormitoryGroup::whereId($this->buildid)->value('groupid');
                if($this->type==1){ //绑定组
                    if(!DormitoryUsersGroup::where(['groupid'=>$groupid,'senselink_id'=>$this->userId])->first()){
                        //添加关联组
                        DormitoryUsersGroup::insert(['groupid'=>$groupid,'senselink_id'=>$this->userId]);
                    }
                    //同步添加到link
                    $senselink->linkperson_edit($this->userId,'','',$groupid);
                }else{ //解绑
                    if(DormitoryUsersGroup::where(['groupid'=>$groupid,'senselink_id'=>$this->userId])->first()){
                        //删除关联组
                        DormitoryUsersGroup::where(['groupid'=>$groupid,'senselink_id'=>$this->userId])->delete();
                    }
                    $i = 0;
                    sync_del_link:
                    //同步删除link关联
                    $res = $senselink->user_group_del($this->userId,$groupid);
                    if($res['code']!=200){//删除失败，重复执行
                        $i++;
                        if($i<3){
                            goto sync_del_link;
                        }
                    }
                }
                sleep(2);//2秒延迟
                $this->delete();
                log::info(date('Y-m-d H:i:s') . '队列--Synclink--执行结束');
            }
        }catch(\Exception $exception){
            //$this->delete();
            Log::error('队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage());
            Log::error('学号 ：'.$this->idnum);
        }
    }
}
