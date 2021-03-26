<?php

namespace Modules\Dorm\Jobs;

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
        file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'开始执行--AllocateBuild--分配宿管楼宇队列任务,入参data:'.json_encode($this->data).PHP_EOL,FILE_APPEND);
        if(empty($this->data)){
            file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s') ."参数异常--Synclink--队列中止".PHP_EOL,FILE_APPEND);
            $this->delete();
        }
        try {
            Log::info($this->attempts());
            // 如果参试大于三次
            if ($this->attempts() > $this->tries) {
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'AllocateBuild--尝试失败次数过多'.PHP_EOL,FILE_APPEND);
                $this->delete();
            } else {
                //非管理员
                foreach($this->data as $v) {
                    if ($v['idnum'] != 'admin') {
                        $builds = DormitoryUsersBuilding::where('idnum', $v['idnum'])->pluck('buildid')->toArray();
                        if(RedisGet('builds-' . $v['idnum'])){
                            Redis::del('builds-' . $v['idnum']);
                        }
                        RedisSet('builds-' . $v['idnum'], $builds, 7200);
                    }
                }
                //管理员
                $abuilds = DormitoryGroup::whereType(1)->pluck('id')->toArray();
                if(RedisGet('builds-admin')){
                    Redis::del('builds-admin');
                }
                RedisSet('builds-admin', $abuilds, 7200);
                $this->delete();
                file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'队列--分配宿管楼宇--执行结束'.PHP_EOL,FILE_APPEND);
            }
        }catch(\Exception $exception){
            $this->delete();
            file_put_contents(storage_path('logs/AllocateBuild.log'),date('Y-m-d H:i:s').'AllocateBuild 队列任务执行失败'."\n".date('Y-m-d H:i:s').','.$exception->getMessage().PHP_EOL,FILE_APPEND);
        }
    }
}
