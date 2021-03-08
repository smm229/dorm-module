<?php

namespace Modules\Dorm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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

    private $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::error(date('Y-m-d H:i:s') .'开始执行--分配宿管楼宇--队列任务');
        try {
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                Log::error('AllocateBuild --尝试失败次数过多');
                $this->delete();
            } else {
                foreach($this->data as $v) {
                    if ($v->idnum != 'admin') {
                        $builds = DormitoryUsersBuilding::where('idnum', $v->idnum)->pluck('buildid')->toArray();
                        if(RedisGet('builds-' . $v->idnum)){
                            Redis::del('builds-' . $v->idnum);
                        }
                        RedisSet('builds-' . $v->idnum, $builds, 7200);
                    }
                }
                $this->delete();
                log::info(date('Y-m-d H:i:s') . '队列--分配宿管楼宇--执行结束');
            }
        }catch(\Exception $exception){
            //$this->delete();
            Log::error('AllocateBuild 队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage());
            Log::error('数据内容：'.json_encode($this->data));
        }
    }
}
