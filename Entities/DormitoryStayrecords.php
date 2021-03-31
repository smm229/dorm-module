<?php

namespace Modules\Dorm\Entities;

use App\Models\Student;
use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Queue;
use Modules\Dorm\Jobs\SyncLink;

class DormitoryStayrecords extends Model
{
    use HasFactory,SerializeDate;

    //声明链接数据库
    //protected $connection = 'mysql_dorm';

    protected $table="dormitory_stayrecords";

    protected $fillable = [];

    /*
     * 登记记录
     * @param data 床位记录，对象/数组
     * @param type 类型 1分配宿舍 2调宿 3退宿
     * @param buildid 原来的宿舍id，调宿用
     */
    public static function record($data,$type,$buildid){
        file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行第二步，增加记录，入参：'.json_encode($data).',type:'.$type.PHP_EOL,FILE_APPEND);
        if($type==3){ //退宿
            self::reverse($data);
        }else{
            $user = Student::where('idnum',$data->idnum)->first();
            if(!$user) return;
            if($type==2){ //调宿舍
                $info = self::where('idnum',$data->idnum)->orderBy('id','desc')->first();
                if($info) self::whereId($info->id)->update(['updated_at'=>date('Y-m-d H:i:s')]);
                //解除关系
                file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--解除绑定关系，调宿用户id：'.$user->senselink_id.'，到宿舍楼'.$buildid.PHP_EOL,FILE_APPEND);
                Queue::push(new SyncLink($user->senselink_id,$buildid,2));
                //SyncLink::dispatch($user->senselink_id,$buildid,2)->delay(3);
            }
            $floornum = DormitoryRoom::whereId($data->roomid)->value('floornum');
            $arr = [
                'username'      =>  $user->username,//姓名
                'idnum'         =>  $data->idnum,//学号
                'sex'           =>  $user->sex_name,//性别
                'gradeName'     =>  $user->grade,//年级
                'collegeName'   =>  $user->collegename,//学院
                'majorName'     =>  $user->majorname,//专业
                'className'     =>  $user->classname,//班级
                'buildName'     =>  $data->build_name,//楼栋
                'floor'         =>  $floornum,//楼层
                'roomnum'       =>  $data->room_num,//房间号
                'bednum'        =>  $data->bednum,//床位号
            ];
            self::insert($arr);
            //绑定关系
            file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--重新分配组关系，调宿用户id：'.$user->senselink_id.'，到宿舍楼'.$data->buildid.PHP_EOL,FILE_APPEND);
            if($user->senselink_id){
                Queue::push(new SyncLink($user->senselink_id,$data->buildid,1));
                //SyncLink::dispatch($data->senselink_id,$data->buildid,1)->delay(5);
            }else {
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--分配失败，无senselinkid，调宿用户idnum：' . $user->idnum . '，到宿舍楼' . $buildid . PHP_EOL, FILE_APPEND);
            }
        }

    }

    /*
     * 批量退宿记录
     * @param array data
     */
    public static function reverse($data){
        file_put_contents(storage_path('logs/stayrecords.log'),date('Y-m-d H:i:s') . '队列--Stayrecords--执行退宿,数据：'.json_encode($data).PHP_EOL,FILE_APPEND);

        foreach($data as $v){
            try {
                $info = self::where('idnum', $v['idnum'])->orderBy('id', 'desc')->first();
                if ($info) self::whereId($info->id)->update(['updated_at' => date('Y-m-d H:i:s')]);
                //解除关系
                $senselink_id = Student::where('idnum', $v['idnum'])->value('senselink_id');
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--批量解除组关系，退宿用户id：' . $senselink_id . PHP_EOL, FILE_APPEND);
                if ($senselink_id) {
                    Queue::push(new SyncLink($senselink_id, $v['buildid'], 2));
                    //SyncLink::dispatch($senselink_id,$v->buildid,2);
                }
            }catch(\Exception $e){
                file_put_contents(storage_path('logs/stayrecords.log'), date('Y-m-d H:i:s') . '队列--Stayrecords--批量退宿解除关系失败用户学号：'.$v['idnum']  . PHP_EOL, FILE_APPEND);
            }
        }

    }
}
