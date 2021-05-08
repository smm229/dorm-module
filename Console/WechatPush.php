<?php

namespace Modules\Dorm\Console;

use App\Models\Classes;
use Illuminate\Console\Command;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/*
 * 推送公众号模板消息
 * Author by xiangyang
 * Email 648128278@qq.com
 * create by 2021-05-08
 */

class WechatPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wechat_push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送模板消息';

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
        //查询每栋楼的数据
        $builds = DormitoryGroup::whereType(1)->get();
        if($builds->first()) {
            foreach($builds as $v) {
                //查询宿管人员信息
                $list = DormitoryUsersBuilding::leftjoin('personnel_oauth','personnel_oauth.idnum','=','dormitory_users_building.idnum')
                            ->select('personnel_oauth.openid')
                            ->where('dormitory_users_building.buildid',$v->id)
                            ->where('personnel_oauth.type',2)
                            ->where('personnel_oauth.channel','mp')
                            ->get();
                if($list->first()) {
                    foreach($list as $vv) {
                        $post_data = array(
                            'touser' => $vv->openid,  //用户openid
                            'template_id' => "iVuv29hmHO7IeDxttnUqJgjzNzf4_WPv16GFpKJBhGc", //在公众号下配置的模板id
                            'url' => env('WEB_URL')."/", //点击模板消息会跳转的链接
                            'topcolor' => "#7B68EE",
                            'data' => array(
                                'first' => array('value' => "昨日数据推送", 'color' => "#FF0000"),
                                'keyword1' => array('value' => date('Y-m-d 00:00:00', strtotime('-1 day')), 'color' => '#FF0000'),  //keyword需要与配置的模板消息对应
                                'keyword2' => array('value' => date('Y-m-d 23:59:59', strtotime('-1 day')), 'color' => '#FF0000'),
                                'remark' => array('value' => '点击详情查看明细', 'color' => '#448aff'),
                            )
                        );
                        Push($post_data);
                    }
                }
            }
        }

        //推送数据给班主任
        $list = Classes::leftjoin('personnel_oauth','personnel_oauth.idnum','=','personnel_classes.idnum')
                ->select('personnel_oauth.*')
                ->where('personnel_oauth.type',2)
                ->where('personnel_oauth.channel','mp')
                ->group('personnel_classes.idnum')
                ->get();
        if($list->first()){
            foreach($list as $v){
                $data = array(
                    'touser' => $v->openid,  //用户openid
                    'template_id' => "iVuv29hmHO7IeDxttnUqJgjzNzf4_WPv16GFpKJBhGc", //在公众号下配置的模板id
                    'url' => env('WEB_URL')."/", //点击模板消息会跳转的链接
                    'topcolor' => "#7B68EE",
                    'data' => array(
                        'first' => array('value' => "昨日数据推送", 'color' => "#FF0000"),
                        'keyword1' => array('value' => date('Y-m-d 00:00:00', strtotime('-1 day')), 'color' => '#FF0000'),  //keyword需要与配置的模板消息对应
                        'keyword2' => array('value' => date('Y-m-d 23:59:59', strtotime('-1 day')), 'color' => '#FF0000'),
                        'remark' => array('value' => '点击详情查看明细', 'color' => '#448aff'),
                    )
                );
                Push($data);
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
