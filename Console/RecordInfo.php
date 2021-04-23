<?php

namespace Modules\Dorm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Modules\Dorm\Entities\DormitoryWarningRecord;
use Modules\Dorm\Entities\DormitoryBlackAccessRecord;
use Modules\Dorm\Entities\DormitoryStrangeAccessRecord;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/*
 * 统计宿管系统首页数据
 * Author by xiangyang
 * Email 648128278@qq.com
 * create by 2021-04-16
 */

class RecordInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record_info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '宿管首页右侧数据，统计通行记录';

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
        $today = date('Y-m-d');
        try {
            //陌生人记录
            $strange = DormitoryStrangeAccessRecord::whereBetween('pass_time',[$today,$today.' 23:59:59'])
                ->orderBy('id','desc')
                ->take(50)
                ->get(['pass_time','cover'])
                ->toArray();
            //黑名单记录
            $black = DormitoryBlackAccessRecord::whereBetween('pass_time',[$today,$today.' 23:59:59'])
                ->orderBy('id','desc')
                ->take(50)
                ->get(['pass_time','cover'])
                ->toArray();
            //非法闯入记录
            $dange = DormitoryWarningRecord::whereBetween('pass_time',[$today,$today.' 23:59:59'])
                ->orderBy('id','desc')->take(50)
                ->get(['pass_time','cover'])
                ->toArray();
            $data = array_merge($strange,$black,$dange);
            //按照时间排序50条
            if(!empty($data)){
                $new = arraySequence($data,'pass_time');
                $res = array_slice($new,0,50);
            }else{
                $res = [];
            }
            RedisSet('record_data', $res, 600);//缓存900S
        }catch(\exception $e){
            file_put_contents(storage_path('logs/record_info.log'),date('Y-m-d H:i:s').'警告通行记录数据统计失败，报错：'.$e->getFile().$e->getLine().$e->getMessage().PHP_EOL,FILE_APPEND);
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
