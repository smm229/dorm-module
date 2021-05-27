<?php

namespace Modules\Dorm\Jobs;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\Visit;

/**
 * 分配访客到用户组
 * Class AllocateGuest
 * @package Modules\Dorm\Jobs
 */
class AllocateGuest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $ids;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        file_put_contents(storage_path('logs/AllocateGuest'),var_export($this->ids).PHP_EOL,FILE_APPEND);
        $guests = Visit::whereIn('id',$this->ids)->get();
        if($guests->isEmpty()){
            return false;
        }
        $senselink = new \senselink();
        foreach($guests as $v){
            if($v->status==2){
                continue;
            }
            $visit_place = explode(',', $v->visit_place);
            $groups = DormitoryGroup::whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
            $groups = implode(',', $groups);
            $headimg['path'] = public_path($v->headimg);
            $imagetype = substr(strrchr($headimg['path'],'.'),1);
            if (strtolower($imagetype) == 'jpg') {
                $imagetype = 'jpeg';
            }
            $receptionUserId = $v->receptionUserId;
            $headimg['imgtype'] = 'image/'.$imagetype;
            $result_link = $senselink->linkguest_add($v->username, $receptionUserId, $headimg, $v->begin_time, $v->end_time, $groups);
            file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',添加访客：'.json_encode($result_link).PHP_EOL,FILE_APPEND);
            if ($result_link['code'] == 30002) {
                //如果link判断人员已存在，则先判断人员步骤如下：1 黑名单（暂时忽略） 2 学生 教职工 3 已存在的访客

                //2.判断学生or教师
                $stuInfo = Student::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
                if ($stuInfo == true) {
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',此人员为校内学生, 无法添加为访客'.PHP_EOL,FILE_APPEND);
                    continue;
                }
                $teacherInfo = Teacher::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
                if ($teacherInfo == true) {
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',此人员为校内教职工, 无法添加为访客'.PHP_EOL,FILE_APPEND);
                    continue;
                }
                $perInfo = Visit::where('link_id', $result_link['data']['similar_user_id'])->first();
                if (!$perInfo) {
                    $result_links = $senselink->linkguest_del($result_link['data']['similar_user_id']);
                    if ($result_links['code'] == 200) {
                        $result_link = $senselink->linkguest_add($v->username, $receptionUserId, $headimg, $v->begin_time, $v->end_time, $groups);
                    }
                } else {
                    file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.',访客已存在'.PHP_EOL,FILE_APPEND);
                    continue;
                }
            }
            if ($result_link['code'] == 200 && isset($result_link['code'])) {
                Visit::whereId($v->id)->update(['status'=>2,'link_id'=>$result_link['data']['id'],'confirm_time'=> date('Y-m-d H:i:s')]);
                continue;
            } else {
                file_put_contents(storage_path('logs/AllocateGuest'),date('Y-m-d H:i:s').'访客id：'.$v->id.','.$result_link['message'].PHP_EOL,FILE_APPEND);
                continue;
            }
        }
        return true;
    }
}
