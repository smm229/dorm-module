<?php

namespace Modules\Dorm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/*
 * 统计宿管系统首页数据
 * Author by xiangyang
 * Email 648128278@qq.com
 * create by 2021-04-15
 */

class Reckon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reckon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计24小时出入数据';

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
            //当日进出数据
            $times['in'] = [
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 01:00:00'])->where('type', 1)->where('direction', '进')->count(),//0点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 02:00:00'])->where('type', 1)->where('direction', '进')->count(),//1点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 03:00:00'])->where('type', 1)->where('direction', '进')->count(),//2点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 04:00:00'])->where('type', 1)->where('direction', '进')->count(),//3点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 05:00:00'])->where('type', 1)->where('direction', '进')->count(),//4点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 06:00:00'])->where('type', 1)->where('direction', '进')->count(),//5点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 07:00:00'])->where('type', 1)->where('direction', '进')->count(),//6点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 08:00:00'])->where('type', 1)->where('direction', '进')->count(),//7点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 09:00:00'])->where('type', 1)->where('direction', '进')->count(),//8点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 10:00:00'])->where('type', 1)->where('direction', '进')->count(),//9点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 11:00:00'])->where('type', 1)->where('direction', '进')->count(),//10点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 12:00:00'])->where('type', 1)->where('direction', '进')->count(),//11点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 13:00:00'])->where('type', 1)->where('direction', '进')->count(),//12点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 14:00:00'])->where('type', 1)->where('direction', '进')->count(),//13点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 15:00:00'])->where('type', 1)->where('direction', '进')->count(),//14点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 16:00:00'])->where('type', 1)->where('direction', '进')->count(),//15点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 17:00:00'])->where('type', 1)->where('direction', '进')->count(),//16点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 18:00:00'])->where('type', 1)->where('direction', '进')->count(),//17点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 19:00:00'])->where('type', 1)->where('direction', '进')->count(),//18点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 20:00:00'])->where('type', 1)->where('direction', '进')->count(),//19点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 21:00:00'])->where('type', 1)->where('direction', '进')->count(),//20点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 22:00:00'])->where('type', 1)->where('direction', '进')->count(),//21点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 23:00:00'])->where('type', 1)->where('direction', '进')->count(),//22点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 23:59:59'])->where('type', 1)->where('direction', '进')->count(),//23点
            ];
            //出
            $times['out'] = [
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 01:00:00'])->where('type', 1)->where('direction', '出')->count(),//0点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 02:00:00'])->where('type', 1)->where('direction', '出')->count(),//1点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 03:00:00'])->where('type', 1)->where('direction', '出')->count(),//2点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 04:00:00'])->where('type', 1)->where('direction', '出')->count(),//3点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 05:00:00'])->where('type', 1)->where('direction', '出')->count(),//4点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 06:00:00'])->where('type', 1)->where('direction', '出')->count(),//5点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 07:00:00'])->where('type', 1)->where('direction', '出')->count(),//6点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 08:00:00'])->where('type', 1)->where('direction', '出')->count(),//7点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 09:00:00'])->where('type', 1)->where('direction', '出')->count(),//8点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 10:00:00'])->where('type', 1)->where('direction', '出')->count(),//9点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 11:00:00'])->where('type', 1)->where('direction', '出')->count(),//10点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 12:00:00'])->where('type', 1)->where('direction', '出')->count(),//11点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 13:00:00'])->where('type', 1)->where('direction', '出')->count(),//12点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 14:00:00'])->where('type', 1)->where('direction', '出')->count(),//13点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 15:00:00'])->where('type', 1)->where('direction', '出')->count(),//14点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 16:00:00'])->where('type', 1)->where('direction', '出')->count(),//15点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 17:00:00'])->where('type', 1)->where('direction', '出')->count(),//16点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 18:00:00'])->where('type', 1)->where('direction', '出')->count(),//17点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 19:00:00'])->where('type', 1)->where('direction', '出')->count(),//18点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 20:00:00'])->where('type', 1)->where('direction', '出')->count(),//19点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 21:00:00'])->where('type', 1)->where('direction', '出')->count(),//20点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 22:00:00'])->where('type', 1)->where('direction', '出')->count(),//21点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 23:00:00'])->where('type', 1)->where('direction', '出')->count(),//22点
                DormitoryAccessRecord::whereBetween('pass_time', [$today, $today . ' 23:59:59'])->where('type', 1)->where('direction', '出')->count(),//23点
            ];
            RedisSet('times_data', $times, 600);//缓存900S
        }catch(\exception $e){
            file_put_contents(storage_path('logs/reckon.log'),date('Y-m-d H:i:s').'数据统计失败，报错：'.$e->getFile().$e->getLine().$e->getMessage().PHP_EOL,FILE_APPEND);
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
