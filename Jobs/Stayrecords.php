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
     * 任务可以执行的最大秒数 (超时时间)。
     *
     * @var int
     */
    public $timeout = 120;

    private $data,$type;

    /*
    * 增加住、退宿记录
    * @param type 类型 1分配宿舍，2调宿 ,3退宿
    */
    public function __construct($beds, $type)
    {
        $this->data = $beds;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                Log::error('邮件参试失败过多');
            } else {
                sleep(2);//2秒延迟
                log::info(date('Y-m-d H:i:s') . '进入队列' . $this->type);
            }
        }catch(\Exception $exception){
            $this->delete();
            Log::error('队列任务执行失败'."\n".date('Y-m-d H:i:s'));
        }
    }



}
