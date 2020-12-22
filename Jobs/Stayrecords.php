<?php

namespace Modules\Dorm\Jobs;

use Illuminate\Bus\Queueable;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Log;
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
    public function __construct($beds, $type, $buildid=0)
    {
        $this->data = $beds;
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
        Log::error(date('Y-m-d H:i:s') .'开始执行--Stayrecords--队列任务');
        try {
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                Log::error('Stayrecords--尝试失败次数过多');
                $this->delete();
            } else {
                //执行住宿记录
                DormitoryStayrecords::record($this->data,$this->type,$this->buildid);
                sleep(2);//2秒延迟
                $this->delete();
                log::info(date('Y-m-d H:i:s') . '队列--Stayrecords--执行结束');
            }
        }catch(\Exception $exception){
            //$this->delete();
            Log::error('队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage());
            Log::error('数据内容：'.json_encode($this->data));
        }
    }

}
