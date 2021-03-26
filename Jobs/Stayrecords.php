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
            $this->delete();
        }
        try {
            file_put_contents(storage_path('logs/stayrecords.log'),'attempts次数'.$this->attempts().PHP_EOL,FILE_APPEND);
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                file_put_contents(storage_path('logs/stayrecords.log'),'Stayrecords--尝试失败次数过多'.PHP_EOL,FILE_APPEND);
                $this->delete();
            } else {
                //执行住宿记录
                DormitoryStayrecords::record($this->data,$this->type,$this->buildid);
                sleep(2);//2秒延迟
                $this->delete();
                file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行结束'.PHP_EOL,FILE_APPEND);
            }
        }catch(\Exception $exception){
            $this->delete();
            file_put_contents(storage_path('logs/stayrecords.log'),'队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage().PHP_EOL,FILE_APPEND);
            file_put_contents(storage_path('logs/stayrecords.log'),'数据内容：'.json_encode($this->data).PHP_EOL,FILE_APPEND);
        }
    }

}
