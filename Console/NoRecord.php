<?php

namespace Modules\Dorm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryConfig;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryNoRecord;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
/*
 * 多日无记录
 */
class NoRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'no_record';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '多日无记录';

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
        //查询系统设定无记录核定参数值为几天、
        $day = DormitoryConfig::getValueByWhere('no_record_day');
        $date = date('Y-m-d',strtotime("-$day day"));//从今天开始连续几天无记录的截止日期
        //循环每一栋楼
        $builds = DormitoryGroup::whereType(1)->get();
        if($builds->first()) {
            foreach ($builds as $v) {
                //取出有记录的
                $idnums = DormitoryAccessRecord::whereIn('id',function ($q) use ($v,$date){
                        $q->from('dormitory_access_record')
                            ->selectRaw('max(id) as id')
                            ->where('buildid',$v->id)
                            ->whereType(1)
                            ->whereBetween('pass_time',[$date,date('Y-m-d 23:59:59',strtotime("-1 day"))]) //时间段内最后一次
                            ->groupBy('idnum')
                            ->get();
                    })
                    ->pluck('idnum')//学号
                    ->toArray();
                //本楼所有学生
                $all_studentids = DormitoryBeds::where('buildid',$v->id)
                    ->whereNotNull('idnum')
                    ->pluck('idnum')
                    ->toArray();
                $diff_ids = array_diff($all_studentids,$idnums);
                if($diff_ids) {
                    //插入未归记录表
                    $students = DormitoryBeds::whereIn('idnum',$diff_ids)
                        ->with('student')
                        ->get()->toArray();
                    array_walk($students, function ($value, $key) use ($date){
                        if($value['student']) {
                            $arr = [
                                'buildid' => $value['buildid'],
                                'type' => 1,
                                'idnum' => $value['idnum'],
                                'username' => $value['student']['username'],
                                'sex' => $value['student']['sex_name'],
                                'college_name' => $value['student']['collegename'],
                                'major_name' => $value['student']['majorname'],
                                'grade_name' => $value['student']['grade'],
                                'class_name' => $value['student']['classname'],
                                'build_name' => $value['build_name'],
                                'roomnum' => $value['room_num'],
                                'bednum' => $value['bednum'],
                                'date' => date('Y-m-d',strtotime("-1 day"))
                            ];
                            DormitoryNoRecord::insert($arr);
                        }
                    });

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
