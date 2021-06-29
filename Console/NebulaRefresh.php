<?php

namespace Modules\Dorm\Console;

use App\Extend\SenseNebula;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\Visit;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * 更新星云M用户,删除无效访客
 * Author by xiangyang
 * Email 648128278@qq.com
 * create by 2021-06-22
 */

class NebulaRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nebula_refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新星云M用户';

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
        set_time_limit(0);
        $date = date('Y-m-d',strtotime('-1 day')); //昨天
        $visit = Visit::whereBetween('end_time',[$date,$date.' 23:59:59'])->get();
        if($visit->first()){
            foreach($visit as $v){
                if($v->img_id){
                    $imgids = unserialize($v->img_id);
                    foreach($imgids as $res) {
                        try {
                            $nebula = new SenseNebula($res['ip']);
                            $param = [
                                [
                                    'name' => 'msg_id',
                                    'contents' => '1031'
                                ],
                                [
                                    'name' => 'lib_id',
                                    'contents' => env('SENSE_NEBULA_WHITE_GROUP') ?? 1
                                ],
                                [
                                    'name' => 'img_id',
                                    'contents' => $res['id']
                                ]
                            ];

                            $rest = $nebula->DelPersonPackage($param);
                            if ($rest['code'] != 0) {
                                throw new \Exception($rest['msg']);
                            }

                        } catch (\Exception $exception) {
                            file_put_contents(storage_path('logs/NebulaSync.log'), date('Y-m-d H:i:s') . '用户：' . $res . '，错误：' . $exception->getLine().$exception->getMessage());
                        }
                    }
                }
            }
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
